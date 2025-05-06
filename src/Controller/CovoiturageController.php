<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Form\CarpoolType;
use App\Repository\CarpoolRepository;
use App\Repository\UserPreferenceRepository;
use App\Repository\PreferenceTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/covoiturage')]
class CovoiturageController extends AbstractController
{
  private $security;
  private $entityManager;

  public function __construct(Security $security, EntityManagerInterface $entityManager)
  {
    $this->security = $security;
    $this->entityManager = $entityManager;
  }

  #[Route('/', name: 'app_covoiturage')]
  public function index(Request $request, CarpoolRepository $carpoolRepository, PreferenceTypeRepository $preferenceTypeRepo, UserPreferenceRepository $userPreferenceRepo): Response
  {
    $depart = $request->query->get('depart');
    $arrivee = $request->query->get('arrivee');
    $date = $request->query->get('date');

    // ID du covoiturage pour afficher les détails
    $carpoolId = $request->query->get('id');
    $selectedCarpool = null;

    // Résultats de recherche
    $carpools = null;

    // Si des critères de recherche sont fournis, effectuer la recherche
    if ($depart || $arrivee || $date) {
      $carpools = $carpoolRepository->search($depart, $arrivee, $date);
    }

    // Si un ID de covoiturage est fourni, récupérer les détails
    if ($carpoolId) {
      $selectedCarpool = $carpoolRepository->find($carpoolId);
    }

    // Récupérer les préférences système
    $systemPreferences = $preferenceTypeRepo->findSystemPreferences();

    // Initialiser la carte des préférences utilisateur
    $userPreferenceMap = [];
    $userCustomPreferences = [];

    // Si l'utilisateur est connecté, récupérer ses préférences
    $user = $this->getUser();
    if ($user) {
      foreach ($systemPreferences as $preference) {
        $preferenceId = $preference->getIdPreferenceType();
        $userPreferenceMap[$preferenceId] = $userPreferenceRepo->userHasPreference($user, $preferenceId);
      }

      $userPreferences = $userPreferenceRepo->findUserPreferences($user);
      $userCustomPreferences = $userPreferenceRepo->findUserCustomPreferences($user);
    } else {
      $userPreferences = [];
    }


    return $this->render('covoiturage/index.html.twig', [
      'depart' => $depart,
      'arrivee' => $arrivee,
      'date' => $date,
      'carpools' => $carpools,
      'selectedCarpool' => $selectedCarpool,
      'systemPreferences' => $systemPreferences,
      'userCustomPreferences' => $userCustomPreferences,
      'userPreferenceMap' => $userPreferenceMap,
      'userPreferences' => $userPreferences ?? []
    ]);
  }

  
  #[Route('/search', name: 'app_covoiturage_search')]
  public function search(Request $request, CarpoolRepository $carpoolRepository, PreferenceTypeRepository $preferenceTypeRepo, UserPreferenceRepository $userPreferenceRepo): Response
  {
    $depart = $request->query->get('depart');
    $arrivee = $request->query->get('arrivee');
    $date = $request->query->get('date');

    // Extraire les noms de ville des adresses complètes si nécessaire
    if ($depart && strpos($depart, ',') !== false) {
      $departParts = explode(',', $depart);
      $depart = trim($departParts[0]);
    }

    if ($arrivee && strpos($arrivee, ',') !== false) {
        $arriveeParts = explode(',', $arrivee);
        $arrivee = trim($arriveeParts[0]);
    }

    // Ajout de la récupération des préférences
    $systemPreferences = $preferenceTypeRepo->findSystemPreferences();
    $userPreferenceMap = [];
    $userCustomPreferences = [];
    $user = $this->getUser();
    if ($user) {
      foreach ($systemPreferences as $preference) {
        $preferenceId = $preference->getIdPreferenceType();
        $userPreferenceMap[$preferenceId] = $userPreferenceRepo->userHasPreference($user, $preferenceId);
      }
      $userPreferences = $userPreferenceRepo->findUserPreferences($user);
      $userCustomPreferences = $userPreferenceRepo->findUserCustomPreferences($user);
    } else {
      $userPreferences = [];
    }


    // Effectuer la recherche
    $carpools = $carpoolRepository->search($depart, $arrivee, $date);

    // Initialiser manuellement les objets pour éviter les problèmes de lazy loading
    foreach ($carpools as $carpool) {
      // Initialiser l'utilisateur et ses relations
      $user = $carpool->getUser();
      $this->entityManager->initializeObject($user);
      
      // Initialiser les voitures de l'utilisateur
      if ($user) {
          $cars = $user->getCars();
          $this->entityManager->initializeObject($cars);
          
          // Initialiser les avis reçus
          $reviews = $user->getRecipientReviews();
          $this->entityManager->initializeObject($reviews);
          
          // Pour chaque avis, initialiser l'expéditeur
          foreach ($reviews as $review) {
              $sender = $review->getSender();
              if ($sender) {
                  $this->entityManager->initializeObject($sender);
              }
          }
          
          // Initialiser les préférences utilisateur
          $userPrefs = $user->getUserPreferences();
          $this->entityManager->initializeObject($userPrefs);
          
          // Pour chaque préférence, initialiser le type
          foreach ($userPrefs as $pref) {
              $prefType = $pref->getPreferenceType();
              if ($prefType) {
                  $this->entityManager->initializeObject($prefType);
              }
          }
      }
      
      // Initialiser les passagers
      $passengers = $carpool->getPassengers();
      $this->entityManager->initializeObject($passengers);
    }

    return $this->render('covoiturage/_search_results.html.twig', [
        'carpools' => $carpools,
        'depart' => $depart,
        'arrivee' => $arrivee,
        'date' => $date,
        'systemPreferences' => $systemPreferences,
        'userCustomPreferences' => $userCustomPreferences,
        'userPreferenceMap' => $userPreferenceMap,
        'userPreferences' => $userPreferences ?? []
    ]);
  }

  
  #[Route('/filter', name: 'app_covoiturage_filter', methods: ['GET', 'POST'])]
  public function filter(Request $request, CarpoolRepository $carpoolRepository): Response
  {
    //Recupere paramatre de recherche covoit
    $depart = $request->query->get('depart');
    $arrivee = $request->query->get('arrivee');
    $date = $request->query->get('date');

    //Recupère parametre de filtre 
    $vehiculeType = $request->query->get('vehicleType', 'allVehicles');
    $passagerCount = (int) $request->query->get('passengerCount', 1);
    $maxCredits = (int) $request->query->get('maxCredits', 50);
    $driverRating = (float) $request->query->get('driverRating', 0.0);

    // Effectuer d'abord la recherche standard
    $carpools = $carpoolRepository->search($depart, $arrivee, $date);
    
    if (empty($carpools)) {
      return $this->render('covoiturage/_search_results.html.twig', [
          'carpools' => [],
          'depart' => $depart,
          'arrivee' => $arrivee,
          'date' => $date,
          'message' => 'Aucun covoiturage trouvé pour cette recherche.'
      ]);
    }

    // Filtrer les résultats avec les critères supplémentaires
    $filteredCarpools = [];
    $filterReasons = [];

    // Mapper les IDs des inputs aux valeurs en base de données
    $fuelTypeMap = [
      'essence' => 'essence',
      'diesel' => 'diesel',
      'hybrid' => 'hybride',
      'electric' => 'electrique'
    ];

    foreach ($carpools as $carpool) {
      $carpoolId = $carpool->getIdCarpool();
      $filterReasons[$carpoolId] = [];
      $passes = true;

       // Filtrer par nombre de places disponibles
      if (!$carpool->canAccomodate($passagerCount)) {
        $filterReasons[$carpoolId][] = 'places_insuffisantes';
        $passes = false;
      }
      // Filtrer par crédits
      if ($carpool->getCredits() > $maxCredits) {
        $filterReasons[$carpoolId][] = 'credits_trop_eleves';
        $passes = false;
      }
      // Filtrer par note du conducteur
      $driver = $carpool->getUser();
      $driverRatingValue = $driver->getRating() ?? 0;
      if ($driverRating > 0 && $driverRatingValue < $driverRating) {
        $filterReasons[$carpoolId][] = 'note_conducteur_insuffisante';
        $passes = false;
      }
      // Filtrer par type de véhicule (si spécifié et différent de "tous")
      if ($vehiculeType !== 'allVehicles') {
        // Récupérer le véhicule du conducteur
        $cars = $driver->getCars();
        
        if ($cars->isEmpty()) {
            $filterReasons[$carpoolId][] = 'aucun_vehicule';
            $passes = false;
        } else {
          // Vérifier si au moins une voiture correspond au type de carburant
          $hasMatchingVehicle = false;
          $fuelTypeToCheck = $fuelTypeMap[$vehiculeType] ?? null;
          // Debug pour vérifier les valeurs
          error_log("Filtering carpool #$carpoolId for fuel type: $fuelTypeToCheck");

          foreach ($cars as $car) {
            // Récupérer l'énergie de la voiture et la convertir en minuscules pour comparaison
            $carFuelType = strtolower($car->getEnergie());
            error_log("Car fuel type: $carFuelType");
            
            // Comparer les types de carburant (insensible à la casse)
            if ($carFuelType === $fuelTypeToCheck) {
                $hasMatchingVehicle = true;
                error_log("Match found!");
                break;
            }
          }
          
          if (!$hasMatchingVehicle) {
              $filterReasons[$carpoolId][] = 'type_carburant_incompatible';
              $passes = false;
          }
        }
      }

      // Ce covoiturage a passé tous les filtres
      if ($passes) {
        $filteredCarpools[] = $carpool;
      }
    }

    // Message spécifique en fonction des résultats
    $message = null;
    if (empty($filteredCarpools)) {
      // Analyser les raisons de filtrage pour donner un message pertinent
      $reasonCounts = [];
      foreach ($filterReasons as $carpoolId => $reasons) {
        foreach ($reasons as $reason) {
          if (!isset($reasonCounts[$reason])) {
            $reasonCounts[$reason] = 0;
          }
          $reasonCounts[$reason]++;
        }
      }

      // Trouver la raison la plus fréquente
      arsort($reasonCounts);
      $topReason = key($reasonCounts);

      switch ($topReason) {
        case 'places_insuffisantes':
          $message = 'Aucun covoiturage ne dispose de suffisamment de places pour le nombre de passagers demandé.';
          break;
        case 'credits_trop_eleves':
          $message = 'Aucun covoiturage ne correspond à votre budget maximum de ' . $maxCredits . ' crédits.';
          break;
        case 'note_conducteur_insuffisante':
          $message = 'Aucun conducteur n\'a une note égale ou supérieure à ' . $driverRating . '.';
          break;
        case 'type_carburant_incompatible':
          $typeLabel = match ($vehiculeType) {
            'essence' => 'Essence',
            'diesel' => 'Diesel',
            'hybrid' => 'Hybride',
            'electric' => 'Électrique',
            default => $vehiculeType
          };
          $message = 'Aucun covoiturage n\'est disponible avec un véhicule de type ' . $typeLabel . '.';
          break;
        default:
          $message = 'Aucun covoiturage ne correspond aux critères de filtrage sélectionnés.';
      }
    }
    
    return $this->render('covoiturage/_search_results.html.twig', [
        'carpools' => $filteredCarpools,
        'depart' => $depart,
        'arrivee' => $arrivee,
        'date' => $date,
        'message' => $message
    ]);
  }


  #[Route('/{id}/details-modal', name: 'app_covoiturage_details_modal')]
  public function getDetailsModal(Carpool $carpool): Response
  {
      return $this->render('covoiturage/_carpool_details.html.twig', [
          'carpool' => $carpool
      ]);
  }
  
  //Route pour joindre un covoiturage
  #[Route('/{id}/join', name: 'app_covoiturage_join', requirements: ['id' => '\d+'], methods: ['POST'])]
  public function joinCarpool(Carpool $carpool, UserRepository $userRepository, CarpoolRepository $carpoolRepository): Response
  {
    // Vérifier si user connecté
    if (!$this->getUser()) {
      // Retourner une réponse JSON pour déclencher l'ouverture de la modal
      return new JsonResponse([
          'success' => false,
          'auth_required' => true,
          'message' => 'Vous devez être connecté pour rejoindre ce covoiturage'
      ], 401);
    }

    $user = $userRepository->findOneByEmail($this->security->getUser()->getUserIdentifier());

    // Vérifier que l'utilisateur a le rôle PASSAGER et non CONDUCTEUR
    if (!$userRepository->isPassenger($user)) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Vous devez avoir le rôle Passager pour rejoindre un covoiturage.'
        ], 403);
    }

    try {
        $result = $carpoolRepository->addPassengerToCarpool($user, $carpool);
        
        // Si l'utilisateur peut rejoindre le covoiturage
        if (isset($result['can_join']) && $result['can_join']) {
            // Déduire les crédits de l'utilisateur
            $userRepository->updateCredits($user, -$carpool->getCredits());
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Vous avez rejoint le covoiturage avec succès !'
            ]);

        } else {
            // L'utilisateur ne peut pas rejoindre le covoiturage
            $responseData = [
                'success' => false,
                'message' => $result['message'] ?? 'Impossible de rejoindre ce covoiturage.'
            ];
            
            // Ajouter les infos supplémentaires si disponibles
            if (isset($result['credits_required'])) {
                $responseData['credits_required'] = $result['credits_required'];
            }
            if (isset($result['user_credits'])) {
                $responseData['user_credits'] = $result['user_credits'];
            }
            if (isset($result['existing_carpool'])) {
                $existingCarpool = $result['existing_carpool'];
                $responseData['existing_carpool'] = [
                    'id' => $existingCarpool->getIdCarpool(),
                    'status' => $existingCarpool->getStatut(),
                    'start_location' => $existingCarpool->getLocationStart(),
                    'end_location' => $existingCarpool->getLocationReach()
                ];
            }
            
            return new JsonResponse($responseData, 400);
        }
    } catch (\Exception $e) {
        // Erreur
        return new JsonResponse([
            'success' => false,
            'message' => 'Une erreur est survenue lors de l\'inscription au covoiturage. Veuillez réessayer.'
        ], 500);
    }

  }

  //Route pour quitter un covoiturage en attente
  #[Route('/{id}/leave', name: 'app_covoiturage_leave', requirements: ['id' => '\d+'], methods: ['POST'])]
  #[IsGranted('ROLE_USER')]
  public function leaveCarpool(Carpool $carpool, CarpoolRepository $carpoolRepository, UserRepository $userRepository): Response
  {
      try {
        $user = $userRepository->findOneByEmail($this->security->getUser()->getUserIdentifier());
        
        // Utiliser la méthode du repository pour retirer le passager
        $result = $carpoolRepository->removePassengerToCarpool($user, $carpool);
        
        if ($result['can_leave']) {
            // Restituer les crédits à l'utilisateur
            $userRepository->updateCredits($user, $carpool->getCredits());
            
            $this->addFlash('success', $result['message'] . ' Vos crédits ont été restitués.');
        } else {
            $this->addFlash('error', $result['message']);
        }
    } catch (\Exception $e) {
        // Gérer les exceptions imprévues
        $this->addFlash('error', 'Une erreur est survenue lors de la désinscription au covoiturage : ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('app_profile');
  }


  
  //Route pour démarer un covoiturage
  #[Route('/{id}/start', name: 'app_covoiturage_start', requirements: ['id' => '\d+'])]
  #[IsGranted('ROLE_USER')]
  public function start(Carpool $carpool, CarpoolRepository $carpoolRepository, UserRepository $userRepository): Response
  {

    $user = $userRepository->findOneByEmail($this->security->getUser()->getUserIdentifier());

    //vérifie si c'est bien le conducteur qui démare
    if ($carpool->getUser() !== $user) {
      $this->addFlash('error', 'Vous ne pouvez pas démarrer un covoiturage dont vous n\'êtes pas le conducteur.');
      return $this->redirectToRoute('app_profile', ['id' => $carpool->getIdCarpool()]);
    }

    //vérifie si le covoiturage et bien en attente
    if (!$carpool->isWaitingCarpool()) {
      $this->addFlash('error', 'Ce covoiturage ne peut etre démarer car il n\'est pas en attente');
      return $this->redirectToRoute('app_profile', ['id' => $carpool->getIdCarpool()]);
    }

    try {
      $carpoolRepository->startCarpool($carpool);
      $this->addFlash('succes', 'le covoiturage a été démarré avec succes.');
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('app_profile');
  }

  //Route pour supprimer un covoiturage
  #[Route('/{id}/cancel', name: 'app_covoiturage_cancel', requirements: ['id' => '\d+'])]
  #[IsGranted('ROLE_USER')]
  public function cancel(Carpool $carpool, CarpoolRepository $carpoolRepository, UserRepository $userRepository): Response
  {
      $user = $userRepository->findOneByEmail($this->security->getUser()->getUserIdentifier());
      
      // Vérifier si c'est bien le conducteur qui annule
      if ($carpool->getUser() !== $user) {
          $this->addFlash('error', 'Vous ne pouvez pas annuler un covoiturage dont vous n\'êtes pas le conducteur.');
          return $this->redirectToRoute('app_profile', ['id' => $carpool->getIdCarpool()]);
      }
      
      try {

        // Rembourser les crédits aux passagers
        foreach ($carpool->getPassengers() as $passenger) {
          $userRepository->updateCredits($passenger, $carpool->getCredits());
        }

        $carpoolRepository->cancelCarpool($carpool);
        $this->addFlash('success', 'Le covoiturage à été annulé et les passager ont été remboursés.');

      } catch (\Exception $e) {
        $this->addFlash('error', $e->getMessage());
      }

      return $this->redirectToRoute('app_profile');
  }

  //Route pour finir un covoiturage
  #[Route('/{id}/finish', name: 'app_covoiturage_finish', requirements: ['id' => '\d+'])]
  #[IsGranted('ROLE_USER')]
  public function finish(Carpool $carpool, CarpoolRepository $carpoolRepository, UserRepository $userRepository): Response
  {
      $user = $userRepository->findOneByEmail($this->security->getUser()->getUserIdentifier());
      
      // Vérifier si c'est bien le conducteur qui termine le trajet
      if ($carpool->getUser() !== $user) {
          $this->addFlash('error', 'Vous ne pouvez pas terminer un covoiturage dont vous n\'êtes pas le conducteur.');
          return $this->redirectToRoute('app_profile', ['id' => $carpool->getIdCarpool()]);
      }
      
      //Vérifier si le covoiturage et actif
      if (!$carpool->isActiveCarpool()) {
          $this->addFlash('error', 'Ce covoiturage ne peut pas être terminé car il n\'est pas actif.');
          return $this->redirectToRoute('app_profile', ['id' => $carpool->getIdCarpool()]);
      }

      try {

        $carpoolRepository->finishCarpool($carpool);

        // Envoyer un email aux passagers pour qu'ils puissent laisser un avis
        // configuration d'envoi d'emails
        
        $this->addFlash('success', 'Le covoiturage a été marqué comme terminé et vos crédits ont été ajoutés apres modération des avis.');

      } catch (\Exception $e) {
        $this->addFlash('error', $e->getMessage());
      }
      
      return $this->redirectToRoute('app_profile');
  }
}
