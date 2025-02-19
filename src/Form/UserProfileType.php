<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    //construction formulaire modification de profil user
    $builder
      // email avec type form
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'attr' => ['class' => 'form-control']
      ])
      // prénom
      ->add('firstname', TextType::class, [
        'label' => 'Prénom',
        'attr' => ['class' => 'form-control']
      ])
      // nom
      ->add('name', TextType::class, [
        'label' => 'Nom',
        'attr' => ['class' => 'form-control']
      ])
      // téléphone si besoin
      ->add('phone_number', TelType::class, [
        'label' => 'Téléphone',
        'required' => false,
        'attr' => ['class' => 'form-control']
      ])
      ->add('profilePicture', FileType::class, [
        'label' => 'Photo de profil',
        'mapped' => false,
        'required' => false,
        'constraints' => [
            new Assert\Image([
                'maxSize' => '1M',
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/bmp', 'image/x-ms-bmp', 'image/webp'],
                'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG ou PNG)',
                'maxSizeMessage' => 'L\'image est trop grande ({{ size }} {{ suffix }}). Maximum autorisé : {{ limit }} {{ suffix }}.',
            ])
        ],
        'attr' => [
            'class' => 'form-control',
            'accept' => 'image/jpeg,image/png'
        ]
        ]);
  }

  // Configure les options du formulaire
  public function configureOptions(OptionsResolver $resolver)
  {
    // Lie le formulaire à l'entité User
    $resolver->setDefaults([
      'data_class' => User::class,
    ]);
  }
}
