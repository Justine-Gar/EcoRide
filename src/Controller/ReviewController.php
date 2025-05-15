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
  #[Route('/review/submit/{carpoolId}', name: 'app_review_submit', requirements: ['carpoolId' => '\d+'], methods: ['POST'])]
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

    if (!$carpool->isCompletedCarpool()) {
      return new JsonResponse(['success' => false, 'message' => 'Vous ne pouvez évaluer que des covoiturages terminés.'], 403);
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

        $token = $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('review_form', $request->request->get('_token'))) {
          return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 400);
        }

        $commentValue = $reviewData['review']['comment'] ?? null;
        $noteValue = isset($reviewData['review']['note']) ? floatval($reviewData['review']['note']) : null;

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
  #[Route('/review/report/{carpoolId}', name: 'app_report_add', requirements: ['carpoolId' => '\d+'], methods: ['POST'])]
  #[isGranted('ROLE_USER')]
  public function addReport(Request $request, int $carpoolId): Response
  {
    $user = $this->getUser();

    try {      
      //récupérer le covoiturage
      $carpool = $this->carpoolRepository->find($carpoolId);
      if (!$carpool) {
        throw new \Exception('Covoiturage introuvable');
      }

      //vérifie que user est bien un passager de ce carpool
      if (!$carpool->getPassengers()->contains($user)) {
        throw new \Exception('Vous n\'avez pas participé à ce covoiturage');
      }

      //vérifie que le covoiturage est terminé
      if (!$carpool->isCompletedCarpool()) {
        throw new \Exception('Vous ne pouvez signaler que le ');
      }

      $reportType = $request->request->get('report_type');
      $description = $request->request->get('description');
      $severity = $request->request->get('severity');

      if (!$reportType || !$description || !$severity) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Tous les champs sont obligatoires.'
      ], 400);
      }

      $data = [
        'report_type' => $reportType,
        'description' => $description,
        'severity' => $severity
      ];
      
      $this->reviewRepository->createReport($data, $user, $carpool);

      return new JsonResponse([
          'success' => true,
          'message' => 'Votre signalement a été enregistré et sera examiné par notre équipe.'
      ]);

    } catch(\Exception $e) {


      return new JsonResponse([
        'success' => false,
        'message' => 'Une erreur est survenue: ' . $e->getMessage()
      ], 500);
    }

    return $this->redirectToRoute('app_profile');
  }
}
