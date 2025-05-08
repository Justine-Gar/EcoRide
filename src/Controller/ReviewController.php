<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Carpool;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Repository\CarpoolRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;


class ReviewController extends AbstractController
{
  private $security;

  public function __construct(
    private ReviewRepository $reviewRepository,
    private CarpoolRepository $carpoolRepository,
    private UserRepository $userRepository,
    private EntityManagerInterface $entityManager,
    Security $security
  ) {
    $this->security = $security;
  }


  /**
    * Traite le formulaire d'avis 
   */
  #[Route('/review/submit/{carpoolId}', name: 'app_review_submit', requirements: ['carpoolId' => '\d+'])]
  #[IsGranted('ROLE_USER')]
  public function submitReview(Request $request, int $carpoolId): Response
  {
    $carpool = $this->carpoolRepository->find($carpoolId);

    if (!$carpool) {
        return new JsonResponse(['success' => false, 'message' => 'Covoiturage non trouvé'], 404);
    }

    // Récupérer l'utilisateur connecté
    $user = $this->getUser();

    // Vérifier que l'utilisateur était passager de ce covoiturage
    if (!$carpool->getPassengers()->contains($user)) {
        return new JsonResponse(['success' => false, 'message' => 'Vous n\'avez pas participé à ce covoiturage'], 403);
    }

    // Vérifier si l'utilisateur a déjà laissé un avis
    $existingReview = $this->reviewRepository->findOneBy([
        'sender' => $user,
        'carpool' => $carpool
    ]);

    if ($existingReview) {
        return new JsonResponse(['success' => false, 'message' => 'Vous avez déjà laissé un avis pour ce covoiturage'], 400);
    }

    try {
        // Récupération correcte des données du formulaire
        $reviewData = $request->request->all();
        // error_log('Données reçues : ' . print_r($reviewData, true));

        // Vérifier si les données ont le format attendu
        if (!isset($reviewData['review']) || !is_array($reviewData['review'])) {
          return new JsonResponse([
              'success' => false, 
              'message' => 'Format de données invalide'
          ], 400);
        }

        $commentValue = $reviewData['review']['comment'] ?? null;
        $noteValue = $reviewData['review']['note'] ?? null;

        if (!$commentValue || !$noteValue) {
          return new JsonResponse([
              'success' => false, 
              'message' => 'Le commentaire et la note sont requis'
          ], 400);
        }

        // Créer l'avis directement à partir des données de la requête
        $review = new Review();
        $review->setComment($commentValue);
        $review->setNote($noteValue);
        $review->setSender($user);
        $review->setRecipient($carpool->getUser());
        $review->setCarpool($carpool);
        $review->setUser($user); 
        $review->setStatut('attente');
        
        // Sauvegarder
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre avis a été soumis et sera publié après modération par l\'équipe.'
        ]);
    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false, 
            'message' => 'Une erreur est survenue: ' . $e->getMessage()
        ], 500);
    }
  }

  /**
   * Traiter le signalement
   */
  #[Route('/review/report/{carpoolId}', name: 'app_report_add', requirements: ['carpoolId'=> '\d+'])]
  #[isGranted('ROLE_USER')]
  public function addReport(Request $request, CarpoolRepository $carpoolRepository): Response
  {
    $user = $this->getUser();

    $carpoolId = $request->request->get('carpool_id');
    $reportType = $request->request->get('report_type');
    $description = $request->request->get('description');
    $severity = $request->request->get('severity');
    $anonymous = $request->request->get('anonymous', 0);

    if (!$carpoolId || !$reportType || !$description || !$severity) {
      $this->addFlash('error', 'Tous les champs sont obligatoires.');
      return $this->redirectToRoute('app_profile');
    }

    try {
      //récupérer le covoiturage
      $carpool = $carpoolRepository->find($carpoolId);
      if (!$carpool) {
        throw new \Exception('Covoiturage introuvable');
      }

      //vérifie que user est bien un passager de ce carpool
      if (!$carpool->getPassengers()->countains($user)) {
        throw new \Exception('Vous n\'avez pas participé à ce covoiturage');
      }

      //vérifie que le covoiturage est terminé
      if (!$carpool->isCompletedCarpool()) {
        throw new \Exception('Vous ne pouvez signaler que le ');
      }

    } catch(\Exception $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('app_profile');
  }
}
