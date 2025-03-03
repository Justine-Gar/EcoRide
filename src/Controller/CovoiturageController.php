<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\User;
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
  public function index(Request $request): Response
  {
    return $this->render('covoiturage/index.html.twig');
  }

  //Route pour la recherche de covoiturage
  #[Route('/search', name: 'app_covoiturage_search')]
  public function search(Request $request, CarpoolRepository $carpoolRepository): Response
  {
    $depart = $request->query->get('depart');
    $arrivee = $request->query->get('arrivee');
    $date = $request->query->get('date');

    $carpools = $carpoolRepository->search($depart, $arrivee, $date);

    return $this->render('covoiturage/search.html.twig', [
      'carpools' => $carpools,
      'depart' => $depart,
      'arrivee' => $arrivee,
      'date' => $date
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
  public function join(Carpool $carpool): Response
  {
    $user = $this->security->getUser();
    //Vérifié sur utilisateur est connecter
    if (!$user) {
      $this->addFlash('error', 'Vous devez être connecté pour participer à un covoiturage.');
      return $this->redirectToRoute('app_login');
    }

    //Vérifier si ce n'est pas un conducteur qui essaie de rejoindre
    //Vérifié le nbr_place restant
    //Vérifier si user a assez de crédits
  
  }
}


//route pour supprimer
