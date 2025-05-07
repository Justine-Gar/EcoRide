<?php

namespace App\Controller;

// Imports nécessaires pour le contrôleur               
use App\Repository\UserRepository;    
use App\Repository\ReviewRepository;
use App\Repository\CarpoolRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

class StaffController extends AbstractController
{

  private $security;

  public function __construct(
      private UserRepository $userRepository,
      private ReviewRepository $reviewRepository,
      private CarpoolRepository $carpoolRepository,
      Security $security
  ) {
      $this->security = $security;
  }

  // Route pour l'interface staff
  // Accessible uniquement aux membres du staff
  #[Route('/staff', name: 'app_staff')]
  #[IsGranted('ROLE_STAFF')]
  public function staff(): Response
  {
      // Récupère le membre du staff connecté
      $user = $this->userRepository->getUser($this->getUser());
      
      // Rend la vue staff
      return $this->render('profile/staff.html.twig', [
          'user' => $user
      ]);
  }
  
}