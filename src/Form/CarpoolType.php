<?php

namespace App\Form;

use App\Entity\Carpool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CarpoolType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('lieuDepart', TextType::class, [
          'label' => 'Lieu de départ',
          'attr' => ['class' => 'form-control mb-2', 'placeholder' => 'Ex: Paris, Gare du Nord']
      ])
      ->add('departLatitude', HiddenType::class)
      ->add('departLongitude', HiddenType::class)
      ->add('lieuArrivee', TextType::class, [
          'label' => 'Lieu d\'arrivée',
          'attr' => ['class' => 'form-control mb-2', 'placeholder' => 'Ex: Lyon, Part-Dieu']
      ])
      ->add('arriveeLatitude', HiddenType::class)
      ->add('arriveeLongitude', HiddenType::class)
      ->add('dateDepart', DateTimeType::class, [
          'label' => 'Date et heure de départ',
          'widget' => 'single_text',
          'attr' => ['class' => 'form-control mb-2']
      ])
      ->add('nbPlaces', IntegerType::class, [
          'label' => 'Nombre de places disponibles',
          'attr' => ['class' => 'form-control mb-2', 'min' => 1, 'max' => 8]
      ])
      ->add('prix', MoneyType::class, [
          'label' => 'Prix par personne (€)',
          'currency' => 'EUR',
          'attr' => ['class' => 'form-control mb-2']
      ])
      ->add('commentaire', TextareaType::class, [
          'label' => 'Commentaire (optionnel)',
          'required' => false,
          'attr' => ['class' => 'form-control mb-2', 'rows' => 3, 'placeholder' => 'Informations supplémentaires sur le trajet...']
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Carpool::class,
    ]);
  }
}