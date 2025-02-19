<?php

namespace App\Form;

use App\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('marque', TextType::class, [
                'label' => 'Marque',
                'attr' => ['class' => 'form-control']
            ])
            ->add('modele', TextType::class, [
                'label' => 'Modèle',
                'attr' => ['class' => 'form-control']
            ])
            ->add('color', TextType::class, [
                'label' => 'Couleur',
                'attr' => ['class' => 'form-control']
            ])
            ->add('energie', ChoiceType::class, [
                'label' => 'Énergie',
                'choices' => [
                    'Électrique' => 'electrique',
                    'Essence' => 'essence',
                    'Diesel' => 'diesel',
                    'Hybride' => 'hybride'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('nbr_places', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 2,
                    'max' => 9
                ]
            ])
            ->add('license_plate', TextType::class, [
                'label' => 'Immatriculation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('first_registration', DateType::class, [
                'label' => 'Date de première immatriculation',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}