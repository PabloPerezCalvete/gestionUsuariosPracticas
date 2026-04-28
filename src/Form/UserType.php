<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{   
   public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('email', EmailType::class)
        ->add('roles', ChoiceType::class, [
            'choices' => [
                'Administrador' => 'ROLE_ADMIN',
                'Usuario Básico' => 'ROLE_USER',
            ],
            'multiple' => true,
            'expanded' => true,
        ])
        ->add('plainPassword', PasswordType::class, [
            'mapped' => false, // No se mapea directamente a la entidad
            'required' => false,
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
