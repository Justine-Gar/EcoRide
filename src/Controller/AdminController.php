<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

class AdminController extends AbstractController 
{
  public function __construct(
    private UserRepository $userRepository,
    private EntityManagerInterface $entityManager,
    private Security $security
  ) {
      $this->userRepository = $userRepository;
      $this->security = $security;
      $this->entityManager = $entityManager;
  }

  // Route pour l'interface administrateur
  #[Route('/admin', name: 'app_admin')]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function admin(): Response
  {
      return $this->redirectToRoute('app_admin_tab');
  }

  // Route pour le tableau de bord administrateur
  #[Route('/admin/tableau-de-bord', name: 'app_admin_tab')]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function dashboard(UserRepository $userRepository): Response
  {
      // Récupère l'administrateur connecté
      $user = $userRepository->getUser($this->getUser());
      
      // Rend la vue admin
      return $this->render('profile/admin/_admin_tableau.html.twig', [
          'user' => $user
      ]);
  }

  //Route pour la gestion des employés
  #[Route('/admin/gestion-employes', name: 'app_admin_gestion_emp')]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function gestionEmployes(UserRepository $userRepository): Response
  {
      $user = $userRepository->getUser($this->getUser());
      
      // Récupérer les employés
      $employes = $userRepository->findAllStaffUser();
      
      return $this->render('profile/admin/_admin_gestion_emp.html.twig', [
          'user' => $user,
          'employes' => $employes
      ]);
  }

  // Route pour la gestion des utilisateurs
  #[Route('/admin/gestion-utilisateurs', name: 'app_admin_gestion_user')]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function gestionUtilisateurs(UserRepository $userRepository): Response
  {
      $user = $userRepository->getUser($this->getUser());
      
      // Récupérer tous les utilisateurs réguliers
      //$utilisateurs = $this->userRepository->findRegularUsers();
      
      return $this->render('profile/admin/_admin_gestion_user.html.twig', [
          'user' => $user,
          //'utilisateurs' => $utilisateurs
      ]);
  }

}