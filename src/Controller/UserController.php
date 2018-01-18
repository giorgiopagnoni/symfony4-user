<?php
/**
 * Created by PhpStorm.
 * User: giorgiopagnoni
 * Date: 17/01/18
 * Time: 10:14
 */

namespace App\Controller;


use App\Entity\User;
use App\Form\User\EditType;
use App\Form\User\RegistrationType;
use App\Form\User\RequestResetPasswordType;
use App\Form\User\ResetPasswordType;
use App\Security\LoginFormAuthenticator;
use App\Service\CaptchaValidator;
use App\Service\Mailer;
use App\Service\TokenGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Route("/user", name="user_")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/register", name="register")
     * @param Request $request
     * @param TokenGenerator $tokenGenerator
     * @param UserPasswordEncoderInterface $encoder
     * @param Mailer $mailer
     * @param CaptchaValidator $captchaValidator
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function register(Request $request, TokenGenerator $tokenGenerator, UserPasswordEncoderInterface $encoder,
                             Mailer $mailer, CaptchaValidator $captchaValidator, TranslatorInterface $translator)
    {
        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();

            try {
                if (!$captchaValidator->validateCaptcha($request->get('g-recaptcha-response'))) {
                    $form->addError(new FormError($translator->trans('captcha.wrong')));
                    throw new ValidatorException('captcha.wrong');
                }

                $user->setPassword($encoder->encodePassword($user, $user->getPassword()));
                $token = $tokenGenerator->generateToken();
                $user->setToken($token);
                $user->setIsActive(false);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $mailer->sendActivationEmailMessage($user);
                $this->addFlash('success', 'user.activation-link');
                return $this->redirect($this->generateUrl('homepage'));
            } catch (ValidatorException $exception) {

            }
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
            'captchakey' => $captchaValidator->getKey()
        ]);
    }

    /**
     * @Route("/activate/{token}", name="activate")
     * @param $request Request
     * @param $user User
     * @param GuardAuthenticatorHandler $authenticatorHandler
     * @param LoginFormAuthenticator $loginFormAuthenticator
     * @return Response
     */
    public function activate(Request $request, User $user, GuardAuthenticatorHandler $authenticatorHandler, LoginFormAuthenticator $loginFormAuthenticator)
    {
        $user->setIsActive(true);
        $user->setToken(null);
        $user->setActivatedAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'user.welcome');

        // automatic login
        return $authenticatorHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $loginFormAuthenticator,
            'main'
        );
    }

    /**
     * @Route("/edit", name="edit")
     * @Security("has_role('ROLE_USER')")
     * @param $request Request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function edit(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $origPwd = $this->getUser()->getPassword();
        $form = $this->createForm(EditType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var User $user */
            $user = $form->getData();
            $pwd = $user->getPassword() ? $encoder->encodePassword($user, $user->getPassword()) : $origPwd;
            $user->setPassword($pwd);
            $em = $this->getDoctrine()->getManager();

            if ($form->isValid()) {
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'user.update.success');

                return $this->redirect($this->generateUrl('homepage'));
            }

            // see http://stackoverflow.com/questions/9812510/symfony2-how-to-modify-the-current-users-entity-using-a-form
            $em->refresh($user);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/request-password-reset", name="request_password_reset")
     * @param Request $request
     * @param TokenGenerator $tokenGenerator
     * @param Mailer $mailer
     * @param CaptchaValidator $captchaValidator
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function requestPasswordReset(Request $request, TokenGenerator $tokenGenerator, Mailer $mailer,
                                         CaptchaValidator $captchaValidator, TranslatorInterface $translator)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $form = $this->createForm(RequestResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                if (!$captchaValidator->validateCaptcha($request->get('g-recaptcha-response'))) {
                    $form->addError(new FormError($translator->trans('captcha.wrong')));
                    throw new ValidatorException('captcha.wrong');
                }
                $repository = $this->getDoctrine()->getRepository(User::class);

                /** @var User $user */
                $user = $repository->findOneBy(['email' => $form->get('_username')->getData(), 'isActive' => true]);
                if (!$user) {
                    $this->addFlash('warning', 'user.not-found');
                    return $this->render('user/request-password-reset.html.twig', [
                        'form' => $form->createView()
                    ]);
                }

                $token = $tokenGenerator->generateToken();
                $user->setToken($token);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $mailer->sendResetPasswordEmailMessage($user);

                $this->addFlash('success', 'user.request-password-link');
                return $this->redirect($this->generateUrl('homepage'));
            } catch (ValidatorException $exception) {

            }
        }

        return $this->render('user/request-password-reset.html.twig', [
            'form' => $form->createView(),
            'captchakey' => $captchaValidator->getKey()
        ]);
    }

    /**
     * @Route("/reset-password/{token}", name="reset_password")
     * @param $request Request
     * @param $user User
     * @param $authenticatorHandler GuardAuthenticatorHandler
     * @param $loginFormAuthenticator LoginFormAuthenticator
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function resetPassword(Request $request, User $user, GuardAuthenticatorHandler $authenticatorHandler,
                                  LoginFormAuthenticator $loginFormAuthenticator, UserPasswordEncoderInterface $encoder)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();
            $user->setPassword($encoder->encodePassword($user, $user->getPassword()));
            $user->setToken(null);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'user.update.success');

            // automatic login
            return $authenticatorHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $loginFormAuthenticator,
                'main'
            );
        }

        return $this->render('user/password-reset.html.twig', ['form' => $form->createView()]);
    }

}