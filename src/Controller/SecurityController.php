<?php
/**
 * Created by PhpStorm.
 * User: giorgiopagnoni
 * Date: 17/01/18
 * Time: 12:34
 */

namespace App\Controller;


use App\Form\Security\LoginType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @Route("/", name="security_")
 */
class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginType::class, [
            '_username' => $lastUsername,
        ]);

        return $this->render(
            'user/security/login.html.twig', [
                'form' => $form->createView(),
                'error' => $error,
            ]
        );
    }

    /**
     * @Route("/logout", name="logout")
     * @throws \Exception
     */
    public function logoutAction()
    {
        throw new \Exception("this should not be reached");
    }
}