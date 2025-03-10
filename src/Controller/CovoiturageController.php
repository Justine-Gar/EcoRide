<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\CarpoolType;
use App\Repository\CarpoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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


  //Route pour la création de covoiturage
  #[Route('/new', name: 'app_covoiturage_new')]
  public function new(Request $request, CarpoolRepository $carpoolRepository): Response
  {
    $user = $this->security->getUser();

    //Vérifie si utilisateur est connecté
    if (!$user) {
      $this->addFlash('error', 'Vous devez être connecté pour proposer un trajet');
      return $this->redirectToRoute('app_login');
    }

    $form =$this->createForm(CarpoolType::class);
    $form->handleRequest($request);

    //vérifie si le formulaire a été envoyé
    if ($form->isSubmitted() && $form->isValid()) {
      $data = $form->getData();

      try {
        //prépare les donné du forml pour allé dans le repo
        $carpoolData = [
          'date_start' => $data->getDateStart()->format('Y-m-d'),
          'hour_start' => $data->getHourStart()->format('H:i'),
          'location_start' => $data->getLocationStart(),
          'lat_start' => $data->getLatStart(),
          'lng_start' => $data->getLngStart(),
          'date_reach' => $data->getDateReach()->format('Y-m-d'),
          'hour_reach' => $data->getHourReach()->format('H:i'),
          'location_reach' => $data->getLocationReach(),
          'lat_reach' => $data->getLatReach(),
          'lng_reach' => $data->getLngReach(),
          'nbr_places' => $data->getNbrPlaces(),
          'credits' => $data->getCredits()
        ];

        $carpool = $carpoolRepository->createCarpool($user, $carpoolData);

        $this->addFlash('success', 'Votre covoiturage a été créé avec succès!');
        return $this->redirectToRoute('app_covoiturage_show',  ['id' => $carpool->getIdCarpool()]);

      } catch (\Exception $e) {
        $this->addFlash('error', $e->getMessage());
      }
    }

    return $this->render('covoiturage/new.html.twig', [
      'form' => $form->createView()
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
}
