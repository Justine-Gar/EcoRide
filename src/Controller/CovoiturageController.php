<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\CarpoolType;
use App\Repository\CarpoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

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
  public function index(Request $request, CarpoolRepository $carpoolRepository): Response
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

    return $this->render('covoiturage/index.html.twig', [
      'depart' => $depart,
      'arrivee' => $arrivee,
      'date' => $date,
      'carpools' => $carpools,
      'selectedCarpool' => $selectedCarpool
    ]);
  }

  
  #[Route('/search', name: 'app_covoiturage_search')]
  public function search(Request $request, CarpoolRepository $carpoolRepository): Response
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

    // Effectuer la recherche
    $carpools = $carpoolRepository->search($depart, $arrivee, $date);

    return $this->render('covoiturage/_search_results.html.twig', [
        'carpools' => $carpools,
        'depart' => $depart,
        'arrivee' => $arrivee,
        'date' => $date
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

  //Route pour joindre un covoiturage
  #[Route('/{id}/join', name: 'app_covoiturage_join', requirements: ['id' => '\d+'])]
  public function join(Carpool $carpool, UserRepository $userRepository): Response
  {
    $securityUser = $this->security->getUser();
    //Vérifié sur utilisateur est connecter
    if (!$securityUser) {
      $this->addFlash('error', 'Vous devez être connecté pour participer à un covoiturage.');
      return $this->redirectToRoute('app_login');
    }

    $user = $userRepository->findOneByEmail($securityUser->getUserIdentifier());

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
    
    // Ajouter l'utilisateur comme passager
    $carpool->addPassenger($user);
    
    // Déduire les crédits de l'utilisateur
    $userRepository->upddateCredits($user, -$carpool->getCredits());
        
    // Sauvegarder les changements
    $this->entityManager->flush();
        
    $this->addFlash('success', 'Vous avez rejoint le covoiturage avec succès !');
    return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
    
  }

  //Route pour démarer un covoiturage
  #[Route('/{id}/start', name: 'app_covoiturage_start', requirements: ['id' => '\d+'])]
  public function start(Carpool $carpool, CarpoolRepository $carpoolRepository): Response
  {
    $user = $this->security->getUser();

    //Vérifie si l'user est connecté

    //vérifier
  }

  //Route pour supprimer un covoiturage
  #[Route('/{id}/cancel', name: 'app_covoiturage_cancel', requirements: ['id' => '\d+'])]
  public function cancel(Carpool $carpool, CarpoolRepository $carpoolRepository, UserRepository $userRepository): Response
  {
      $user = $this->security->getUser();
      
      // Vérifier si l'utilisateur est connecté
      if (!$user) {
          $this->addFlash('error', 'Vous devez être connecté pour annuler un covoiturage.');
          return $this->redirectToRoute('app_login');
      }
      
      // Vérifier si c'est bien le conducteur qui annule
      if ($carpool->getUser() !== $user) {
          $this->addFlash('error', 'Vous ne pouvez pas annuler un covoiturage dont vous n\'êtes pas le conducteur.');
          return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
      }
      
      // Rembourser les crédits aux passagers
      foreach ($carpool->getPassengers() as $passenger) {
        $userRepository->updateCredits($passenger, $carpool->getCredits());
      }
      
      // Changer le statut du covoiturage
      $carpoolRepository->updateStatus($carpool, 'annulé');
      
      
      $this->addFlash('success', 'Le covoiturage a été annulé et les passagers ont été remboursés.');
      return $this->redirectToRoute('app_covoiturage');
  }

  //Route pour finir un covoiturage
  #[Route('/{id}/finish', name: 'app_covoiturage_finish', requirements: ['id' => '\d+'])]
  public function finish(Carpool $carpool, CarpoolRepository $carpoolRepository, UserRepository $userRepository): Response
  {
      $user = $this->security->getUser();
      
      // Vérifier si l'utilisateur est connecté
      if (!$user) {
          $this->addFlash('error', 'Vous devez être connecté pour terminer un covoiturage.');
          return $this->redirectToRoute('app_login');
      }
      
      // Vérifier si c'est bien le conducteur qui termine le trajet
      if ($carpool->getUser() !== $user) {
          $this->addFlash('error', 'Vous ne pouvez pas terminer un covoiturage dont vous n\'êtes pas le conducteur.');
          return $this->redirectToRoute('app_covoiturage_show', ['id' => $carpool->getIdCarpool()]);
      }
      
      // Changer le statut du covoiturage
      $carpoolRepository->updateStatus($carpool, 'terminé');
      
      // Créditer le conducteur
      $userRepository->updateCredits($user, $carpool->getCredits());
      
      // Envoyer un email aux passagers pour qu'ils puissent laisser un avis
      // Cette partie dépend de votre configuration d'envoi d'emails
      
      $this->addFlash('success', 'Le covoiturage a été marqué comme terminé et vos crédits ont été ajoutés.');
      return $this->redirectToRoute('app_profile');
  }
}
