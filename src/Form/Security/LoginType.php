<?php

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_username', null, [
                'label' => 'user.email'
            ])
            ->add('_password', PasswordType::class, [
                'label' => 'user.password.first'
            ])
            ->add('remember_me', CheckboxType::class, [
                'label' => 'rememberme',
                'required' => false
            ]);
    }
}
