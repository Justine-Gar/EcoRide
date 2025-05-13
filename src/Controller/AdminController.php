<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController 
{
  public function __construct(
    private RoleRepository $roleRepository,
    private UserRepository $userRepository,
    private ReviewRepository $reviewRepository,
    private EntityManagerInterface $entityManager,
    private UserPasswordHasherInterface $passwordHasher,
    private Security $security,
    private RequestStack $requestStack
  ) {
      $this->userRepository = $userRepository;
      $this->reviewRepository = $reviewRepository;
      $this->security = $security;
      $this->requestStack = $requestStack;
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

  //Route pour ajouter un employé
  #[Route('/admin/gestion-employes/add', name: 'app_admin_add_employe', methods: ['POST'])]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function addEmploye(Request $request, UserRepository $userRepository, RoleRepository $roleRepository, UserPasswordHasherInterface $passwordHasher): Response
  {
    try {
      // Vérifie le token CSRF
      $submittedToken = $request->request->get('_csrf_token');
      if (!$this->isCsrfTokenValid('add_employee', $submittedToken)) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
              'success' => false,
              'message' => 'Token CSRF invalide, veuillez rafraîchir la page et réessayer.'
          ], 403);
        }
        $this->addFlash('error', 'Token CSRF invalide, veuillez rafraîchir la page et réessayer.');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Récupere les données
      $name = $request->request->get('name');
      $firstname = $request->request->get('firstname');
      $email = $request->request->get('email');
      $phone_number = $request->request->get('phone_number');
      $password = $request->request->get('password');

      // Vérifie les données
      if (!$name || !$firstname || !$email || !$phone_number || !$password) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Tous les champs sont obligatoires'
          ], 400);
        }
        
        $this->addFlash('error', 'Tous les champs sont obligatoires');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Vérifie format de l'email
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Format d\'email invalide'
          ], 400);
        }
        
        $this->addFlash('error', 'Format d\'email invalide');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Vérifie si email existe déjà
      if ($userRepository->findOneByEmail($email)) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Un utilisateur avec cet email existe déjà'
          ], 400);
        }
        
        $this->addFlash('error', 'Un utilisateur avec cet email existe déjà');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Vérifie longueur de mdp
      if (strlen($password) < 8) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Le mot de passe doit contenir au moins 8 caractères'
          ], 400);
        }
        
        $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Creation du nouvelle user
      $user = new User();
      $user->setName($name);
      $user->setFirstname($firstname);
      $user->setEmail($email);
      $user->setPhoneNumber($phone_number);
      $user->setPassword($passwordHasher->hashPassword($user, $password));
      $user->setCredits(100);
      // Récupération du rôle Staff
      $staffRole = $roleRepository->findByName('Staff');
      if (!$staffRole) {
          throw new \Exception('Le rôle Staff n\'existe pas');
      }
      $user->addRole($staffRole);

      $userRepository->save($user, true);

      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => true,
          'message' => 'Employé ajouté avec succès !',
          'employee' => [
            'id' => $user->getIdUser(),
            'name' => $user->getName(),
            'firstname' => $user->getFirstname(),
            'email' => $user->getEmail()
          ]
        ]);
      }

      $this->addFlash('success', 'Employé ajouté avec succès !');
      return $this->redirectToRoute('app_admin_gestion_emp');

    } catch (\Exception $e) {
      // Gestion des erreurs
      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Une erreur est survenue lors de l\'ajout de l\'employé : ' . $e->getMessage()
        ], 500);
      }

      $this->addFlash('error', 'uen erreur est survenue lors de l\'ajout de l\'employé !');
      return $this->redirectToRoute('app_admin_gestion_emp');
    }
  }

  //Route pour supprimer un employé
  #[Route('/admin/gestion-employes/delete/{id}', name: 'app_admin_delete_employe', methods: ['POST'])]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function deleteEmploye(Request $request, int $id, UserRepository $userRepository, RoleRepository $roleRepository): Response
  {
    try {
      // Vérifie le token CSRF
      $submittedToken = $request->request->get('_csrf_token');
      if (!$this->isCsrfTokenValid('delete_employee', $submittedToken)) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Token CSRF invalide, veuillez rafraîchir la page et réessayer.'
          ], 403);
        }

        $this->addFlash('error', 'Token CSRF invalide, veuillez rafraîchir la page et réessayer.');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Récupérere user ID
      $employee = $userRepository->findOneById($id);

      if (!$employee) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Employé non trouvé'
          ], 404);
        }

        $this->addFlash('error', 'Employé non trouvé');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Vérifie si user a role staff
      $staffRole = $roleRepository->findByName('Staff');
      if (!$staffRole || !$employee->hasRole($staffRole)) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Cet utilisateur n\'est pas un membre du staff'
          ], 400);
        }

        $this->addFlash('error', 'Cet utilisateur n\'est pas un membre du staff');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      // Supprimer user
      $userRepository->remove($employee, true);

      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => true,
          'message' => 'Employé supprimé avec succès!'
        ]);
      }

      $this->addFlash('success', 'Employé supprimé avec succès!');
      return $this->redirectToRoute('app_admin_gestion_emp');

    } catch (\Exception $e) {
      // Gestion des erreurs
      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Une erreur est survenue lors de la supression de l\'employé ! : ' . $e->getMessage()
        ], 500);
      }
      $this->addFlash('error', 'Une erreur est survenue lors de la supression de l\'employé !');
      return $this->redirectToRoute('app_admin_gestion_emp');
    }
  }

  //Route pour obtenir les informations d'un employé
  #[Route('/admin/gestion-employes/{id}', name: 'app_admin_get_employe', methods: ['GET'])]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function getEmploye(Request $request, int $id, UserRepository $userRepository, RequestStack $requestStack): Response
  {
    try {
      $employee = $userRepository->findOneById($id);

      if (!$employee) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Employé non trouvé'
          ], 404);
        }

        $this->addFlash('error', 'Employé non trouvé');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }

      if ($requestStack->getCurrentRequest()->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => true,
          'employee' => [
            'id' => $employee->getIdUser(),
            'name' => $employee->getName(),
            'firstname' => $employee->getFirstname(),
            'email' => $employee->getEmail(),
            'phone_number' => $employee->getPhoneNumber(),
          ]
        ]);
      }

      return $this->redirectToRoute('app_admin_gestion_emp');

    } catch (\Exception $e) {
      if ($requestStack->getCurrentRequest()->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Une erreur est survenue: ' . $e->getMessage()
        ], 500);
      }

      $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
      return $this->redirectToRoute('app_admin_gestion_emp');
    }
  }

  //Route pour modifier un employé
  #[Route('/admin/gestion-employes/update/{id}', name: 'app_admin_update_employe', methods: ['POST', 'PUT'])]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function updateEmploye(Request $request, int $id, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
  {
    try {
       // Vérifie le token CSRF
      $submittedToken = $request->request->get('_csrf_token');
      if (!$this->isCsrfTokenValid('update_employee', $submittedToken)) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Token CSRF invalide, veuillez rafraîchir la page et réessayer.'
          ], 403);
        }
        
        $this->addFlash('error', 'Token CSRF invalide, veuillez rafraîchir la page et réessayer.');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }
      
      // Récupére user ID
      $employee = $userRepository->findOneById($id);
      
      if (!$employee) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Employé non trouvé'
          ], 404);
        }
          
        $this->addFlash('error', 'Employé non trouvé');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }
      
      // Récupérer les données
      $name = $request->request->get('name');
      $firstname = $request->request->get('firstname');
      $email = $request->request->get('email');
      $phone_number = $request->request->get('phone_number');
      $password = $request->request->get('password');
      
      // Valider les données
      if (!$name || !$firstname || !$email || !$phone_number) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Nom, prénom, email et téléphone sont obligatoires'
          ], 400);
        }
          
        $this->addFlash('error', 'Nom, prénom, email et téléphone sont obligatoires');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }
      
      // V"rifie format email
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Format d\'email invalide'
          ], 400);
        }
          
        $this->addFlash('error', 'Format d\'email invalide');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }
      
      // Vérifie email existe déjà 
      $existingUser = $userRepository->findOneByEmail($email);
      if ($existingUser && $existingUser->getIdUser() !== $employee->getIdUser()) {
        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Un autre utilisateur avec cet email existe déjà'
          ], 400);
        }

        $this->addFlash('error', 'Un autre utilisateur avec cet email existe déjà');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }
      
      // Mise à jour des infos
      $employee->setName($name);
      $employee->setFirstname($firstname);
      $employee->setEmail($email);
      $employee->setPhoneNumber($phone_number);

      // Si donné mise a jour du mdp
      if ($password && strlen($password) >= 8) {
        $employee->setPassword($passwordHasher->hashPassword($employee, $password));
      } elseif ($password) {

        if ($request->isXmlHttpRequest()) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Le mot de passe doit contenir au moins 8 caractères'
          ], 400);
        }

        $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères');
        return $this->redirectToRoute('app_admin_gestion_emp');
      }
      
      // Save les modif
      $userRepository->save($employee, true);

      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => true,
          'message' => 'Employé mis à jour avec succès!',
          'employee' => [
            'id' => $employee->getIdUser(),
            'name' => $employee->getName(),
            'firstname' => $employee->getFirstname(),
            'email' => $employee->getEmail()
          ]
        ]);
      }

      $this->addFlash('success', 'Employé supprimer avec succès !');
      return $this->redirectToRoute('app_admin_gestion_emp');

    } catch (\Exception $e) {
      // Gestion des erreurs
      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Une erreur est survenue lors de la supression de l\'employé ! : ' . $e->getMessage()
        ], 500);
      }
      $this->addFlash('error', 'Une erreur est survenue lors de la supression de l\'employé !');
      return $this->redirectToRoute('app_admin_gestion_emp');
    }
  }


  // Route pour la gestion des utilisateurs
  #[Route('/admin/gestion-utilisateurs', name: 'app_admin_gestion_user')]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function gestionUtilisateurs(UserRepository $userRepository, ReviewRepository $reviewRepository): Response
  {
      $user = $userRepository->getUser($this->getUser());
      
      //Recup^érer tout users qui ne sont pas staff et admin
      $allUsers = $userRepository->findRegularUsers();
      //Récupérer les user avec un signalement danger
      $allWarningUsers = $reviewRepository->findAllWarningUsers();

      // Organiser les données pour l'affichage
      $usersWithWarnings = [];
      
      foreach ($allWarningUsers as $review) {
          $recipient = $review->getRecipient();
          $userId = $recipient->getIdUser();
          
          if (!isset($usersWithWarnings[$userId])) {
              $usersWithWarnings[$userId] = [
                  'user' => $recipient,
                  'reviews' => [],
                  'count' => 0,
                  'lastWarning' => null
              ];
          }
          
          $usersWithWarnings[$userId]['reviews'][] = $review;
          $usersWithWarnings[$userId]['count']++;
          
          $carpool = $review->getCarpool();

          if ($carpool) {
            $carpoolDate = $carpool->getDateReach(); 
            // Si c'est le premier signalement ou si ce covoiturage est plus récent
            if ($usersWithWarnings[$userId]['lastWarning'] === null || 
                ($carpoolDate !== null && $carpoolDate > $usersWithWarnings[$userId]['lastWarning'])) {
                $usersWithWarnings[$userId]['lastWarning'] = $carpoolDate;
            }
          }
      }

      return $this->render('profile/admin/_admin_gestion_user.html.twig', [
          'user' => $user,
          'utilisateurs' => $allUsers,
          'usersWithWarnings' => $usersWithWarnings
      ]);
  }

  //Route pour supprimer un utilisateurs
  #[Route('/admin/gestion-utilisateurs/delete/{id}', name: 'app_admin_delete_user')]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function deleteUser(Request $request, int $id, UserRepository $userRepository): Response
  {
    $utilisateur = $userRepository->findOneById($id);
    
    if (!$utilisateur) {
      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Utilisateur non trouvé.'
        ]);
      }
      $this->addFlash('error', 'Utilisateur non trouvé.');
      return $this->redirectToRoute('app_admin_gestion_user');
    }
    
    // Vérifier que l'utilisateur n'est pas admin ou staff
    if ($utilisateur->hasRoleByName('Administrateur') || $utilisateur->hasRoleByName('Staff')) {
      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Vous ne pouvez pas supprimer un administrateur ou un membre du staff.'
        ]);
      }
      $this->addFlash('error', 'Vous ne pouvez pas supprimer un administrateur ou un membre du staff.');
      return $this->redirectToRoute('app_admin_gestion_user');
    }
    
    try {
      $userRepository->remove($utilisateur, true);

      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => true,
          'message' => 'Utilisateur supprimé avec succès.'
        ]);
      }
      $this->addFlash('success', 'Utilisateur supprimé avec succès.');

    } catch (\Exception $e) {

      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage()
        ]);
      }
      $this->addFlash('error', 'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage());
    }
      
    return $this->redirectToRoute('app_admin_gestion_user');
  }

  //Route pour détail d'un signalement
  #[Route('/admin/gestion-utilisateurs/warnings/{id}', name: 'app_admin_user_warnings', methods: ['GET'])]
  #[IsGranted('ROLE_ADMINISTRATEUR')]
  public function getUserWarnings(Request $request, int $id, UserRepository $userRepository, ReviewRepository $reviewRepository): Response
  {
    $user = $userRepository->findOneById($id);

    if (!$user) {
      if ($request->isXmlHttpRequest()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Utilisateur non trouvé'
        ], 404);
      }

      $this->addFlash('error', 'Utilisateur non trouvé');
      return $this->redirectToRoute('app_admin_gestion_user');
    }

    // Récupérer les signalements de l'utilisateur
    $warnings = $reviewRepository->findUserWarnings($user);

    if ($request->isXmlHttpRequest()) {
      return $this->render('profile/admin/_admin_warningUser_details.html.twig', [
        'user' => $user,
        'warnings' => $warnings,
        'userData' => [
          'lastWarning' => !empty($warnings) ? $warnings[0]->getCarpool()->getDateReach() : null,
          'count' => count($warnings)
        ]
      ]);
    }

    return $this->redirectToRoute('app_admin_gestion_user');
  }

}