<?php

namespace App\Form;

use App\Entity\Grupo;
use App\Entity\Usuario1;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GrupoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', null, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('descripcion', null, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('users', EntityType::class, [
                'class' => Usuario1::class,
                'choice_label' => 'email',
                'multiple' => true,
                'expanded' => true, // Checkboxes
                'by_reference' => false, // OBLIGATORIO para que removeUser funcione
                'label' => 'Miembros del grupo',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Grupo::class,
        ]);
    }
}