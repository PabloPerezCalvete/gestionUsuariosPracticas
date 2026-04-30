<?php

namespace App\Form;


use App\Entity\Grupo;
use App\Entity\Usuario1;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
                'mapped' => false,
                'required' => false,
            ])

            ->add('grupo', EntityType::class, [
                'class' => Grupo::class,
                'choice_label' => 'nombre',
                'placeholder' => 'Sin grupo',
                'required' => false,
                'label' => 'Grupo'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario1::class,
        ]);
    }
}