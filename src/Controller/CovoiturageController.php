<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\User;
use App\Repository\UserRepository;
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
  public function filter(Request $request, CarpoolRepository $carpoolRepository, UserRepository $userRepository): Response
  {
    //Recupere paramatre de recherche covoit
    $depart = $request->query->get('depart');
    $arrivee = $request->query->get('arrivee');
    $date = $request->query->get('date');
    //Recupère parametre de filtre 
    $vehiculeType = $request->query->get('vehicleType', 'allVehicles');
    $passagerCount = (int) $request->query->get('passengerCount', 1);
    $maxCredits = (int) $request->query->get('maxCredits', 50);
    $driverRating = (float) $request->query->get('driverRating', 5.0);

    // Effectuer d'abord la recherche standard
    $carpools = $carpoolRepository->search($depart, $arrivee, $date);
    
    // Filtrer les résultats avec les critères supplémentaires
    $filteredCarpools = [];
    
    foreach ($carpools as $carpool) {
        // Filtrer par nombre de places disponibles
        if (!$carpool->canAccomodate($passagerCount)) {
          continue;
        }
        
        // Filtrer par crédits
        if ($carpool->getCredits() > $maxCredits) {
            continue;
        }
        
        // Filtrer par note du conducteur
        $driver = $carpool->getUser();
        $driverRatingValue = $driver->getRating() ?? 0; // Utiliser 5 comme valeur par défaut si pas de note
        
        if ($driverRatingValue < $driverRating) {
            continue;
        }
        
        // Filtrer par type de véhicule (si spécifié et différent de "tous")
        if ($vehiculeType !== 'allVehicles') {
            // Récupérer le véhicule du conducteur
            $cars = $driver->getCars();
            
            if ($cars->isEmpty()) {
                continue; // Pas de véhicule
            }
            
            // Vérifier si au moins une voiture correspond au type de carburant
            $hasMatchingVehicle = false;
            
            foreach ($cars as $car) {
                // Mapper les IDs des inputs aux valeurs en base de données
                $fuelTypeMap = [
                    'essence' => 'Essence',
                    'diesel' => 'Diesel',
                    'hybrid' => 'Hybride',
                    'electric' => 'Électrique'
                ];
                
                $fuelTypeToCheck = $fuelTypeMap[$vehiculeType] ?? null;
                
                if ($car->getFuelType() === $fuelTypeToCheck) {
                    $hasMatchingVehicle = true;
                    break;
                }
            }
            
            if (!$hasMatchingVehicle) {
                continue;
            }
        }
        
        // Ce covoiturage a passé tous les filtres
        $filteredCarpools[] = $carpool;
    }
    
    return $this->render('covoiturage/_search_results.html.twig', [
        'carpools' => $filteredCarpools,
        'depart' => $depart,
        'arrivee' => $arrivee,
        'date' => $date
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
  #[Route('/{id}/join', name: 'app_covoiturage_join', requirements: ['id' => '\d+'])]
  #[IsGranted('ROLE_USER')]
  public function join(Carpool $carpool, UserRepository $userRepository): Response
  {

    $user = $userRepository->findOneByEmail($this->security->getUser()->getUserIdentifier());

    //Vérifier si ce n'est pas le conducteur qui essaie de rejoindre
    if ($carpool->getUser() === $user) {
      $this->addFlash('error', 'Vous ne pouvez pas rejoindre votre propre covoiturage.');
      return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
    }

    // Vérifier si l'utilisateur n'est pas déjà inscrit
    if ($carpool->getPassengers()->contains($user)) {
      $this->addFlash('warning', 'Vous êtes déjà inscrit à ce covoiturage.');
      return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
    }

    //Vérifié le nbr_place restant
    if ($carpool->getPassengers()->count() >= $carpool->getNbrPlaces()) {
        $this->addFlash('error', 'Il n\'y a plus de places disponibles pour ce covoiturage.');
        return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
    }
    //Vérifier si user a assez de crédits
    if ($user->getCredits() < $carpool->getCredits()) {
      $this->addFlash('error', 'Vous n\'avez pas assez de crédits pour rejoindre ce covoiturage.');
      return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
    }

    try {

      // Ajouter l'utilisateur comme passager
      $carpool->addPassenger($user);
      // Déduire les crédits de l'utilisateur
      $userRepository->updateCredits($user, -$carpool->getCredits());
      // Sauvegarder les changements
      $this->entityManager->flush();
      $this->addFlash('success', 'Vous avez rejoint le covoiturage avec succès !');

    } catch(\Exception $e) {

      $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription au covoiturage. Veuillez réessayer.');
    }

    return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
    
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
      return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
    }

    //vérifie si le covoiturage et bien en attente
    if (!$carpool->isWaitingCarpool()) {
      $this->addFlash('error', 'Ce covoiturage ne peut etre démarer car il n\'est pas en attente');
      return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
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
          return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
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
          return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
      }
      
      //Vérifier si le covoiturage et actif
      if (!$carpool->isActiveCarpool()) {
          $this->addFlash('error', 'Ce covoiturage ne peut pas être terminé car il n\'est pas actif.');
          return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
      }

      try {

        $carpoolRepository->finishCarpool($carpool);

        // Créditer le conducteur
        $userRepository->updateCredits($user, $carpool->getCredits());

        // Envoyer un email aux passagers pour qu'ils puissent laisser un avis
        // configuration d'envoi d'emails
        
        $this->addFlash('success', 'Le covoiturage a été marqué comme terminé et vos crédits ont été ajoutés.');

      } catch (\Exception $e) {
        $this->addFlash('error', $e->getMessage());
      }
      
      return $this->redirectToRoute('app_profile');
  }
}
