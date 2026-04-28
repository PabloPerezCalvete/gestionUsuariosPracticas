<?php
namespace App\Form;

use App\Entity\Equipo;
use App\Entity\Usuario1; // <--- ASEGÚRATE DE QUE ESTE SEA EL NOMBRE DE TU ENTIDAD
use Symfony\Bridge\Doctrine\Form\Type\EntityType; // <--- NECESARIO PARA RELACIONES
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marca', TextType::class, ['attr' => ['placeholder' => 'Ej: Dell, HP...']])
            ->add('modelo', TextType::class)
            ->add('numeroSerie', TextType::class, ['label' => 'Nº de Serie'])
            ->add('tipo', ChoiceType::class, [
                'choices' => [
                    'Portátil' => 'laptop',
                    'Sobremesa' => 'desktop',
                    'Servidor' => 'server',
                ],
            ])
            ->add('estado', ChoiceType::class, [
                'choices' => [
                    'Operativo' => 'ok',
                    'En reparación' => 'repair',
                    'Baja' => 'retired',
                ],
            ])
            // --- AÑADE ESTO AQUÍ ---
            ->add('propietario', EntityType::class, [
                'class' => Usuario1::class, // La clase de la entidad relacionada
                'choice_label' => 'email',   // Qué propiedad del usuario mostrar en el select
                'placeholder' => 'Sin asignar (Stock)',
                'required' => false,        // Permite que un equipo no tenga dueño
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Equipo::class]);
    }
}