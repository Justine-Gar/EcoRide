<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Carpool;              
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
        private EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->security = $security;
    }

    // Route pour l'interface staff
    // Accessible uniquement aux membres du staff
    #[Route('/staff', name: 'app_staff')]
    #[IsGranted('ROLE_STAFF')]
    public function index(): Response
    {
        // Récupère le membre du staff connecté
        $user = $this->userRepository->getUser($this->getUser());

        //Récupérer les avis en attente de modé
        $waitReviews = $this->reviewRepository->findPendingReviews();
        //récupérer les signalement
        $reportReviews = $this->reviewRepository->findReport();
        //Récupérer les statistque des avis
        $reviewStats = $this->reviewRepository->getReviewStats();

        //Nombre d'avis en attente
        $waitCount = count($waitReviews);

        //Nombre d'avis traité (approuvé et rejetés)
        $reviewsCount = ($reviewStats['publié']['count'] ?? 0) + ($reviewStats['rejeté']['count'] ?? 0);

        // Rend la vue staff
        return $this->render('profile/staff.html.twig', [
            'user' => $user,
            'pendingReviews' => $waitReviews,
            'reportReviews' => $reportReviews,
            'pendingCount' => $waitCount,
            'processedCount' => $reviewsCount,
            'reportedCount' => 5,
        ]);
    }


    // Route pour approuver un avis
    #[Route('/staff/reviews/approve/{id}', name: 'app_staff_approve_review', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function approveReview(Review $review, Request $request): Response
    {
        try {
            // Approuver l'avis
            $this->reviewRepository->moderateReview($review, 'publié');
            
            // Mettre à jour la note moyenne de l'utilisateur
            $user = $review->getRecipient();
            $this->userRepository->updateRating($user);
            
            $this->addFlash('success', 'L\'avis a été approuvé avec succès.');
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true]);
            }
            
            return $this->redirectToRoute('app_staff');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
            
            return $this->redirectToRoute('app_staff');
        }
    }

    // Route pour rejeter un avis
    #[Route('/staff/reviews/reject/{id}', name: 'app_staff_reject_review', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function rejectReview(Review $review, Request $request): Response
    {
        try {
            // Rejeter l'avis
            $this->reviewRepository->moderateReview($review, 'rejeté');
            
            $this->addFlash('success', 'L\'avis a été rejeté.');
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true]);
            }
            
            return $this->redirectToRoute('app_staff');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
            
            return $this->redirectToRoute('app_staff');
        }
    }



    // Route pour voir les détails d'un covoiturage signalé
    #[Route('/staff/carpools/details/{id}', name: 'app_staff_carpool_details')]
    public function carpoolDetails(Carpool $carpool): Response
    {

        return $this->render('profile/staff/_staff_carpool_details.html.twig', [
            'carpool' => $carpool
        ]);
    }
    
    // Route pour marquer un signalement comme résolu
    #[Route('/staff/reports/resolve/{id}', name: 'app_staff_resolve_report', methods: ['POST'])]
    public function resolveReport(Request $request, int $id): Response
    {
        // Fonction à implémenter quand le système de signalements sera en place
        
        $this->addFlash('success', 'Le signalement a été résolu.');
        
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }
        
        return $this->redirectToRoute('app_staff');
    }
    









    // Route pour afficher l'historique des actions du staff
    #[Route('/staff/history', name: 'app_staff_history')]
    public function actionHistory(): Response
    {
        // Fonction à implémenter pour afficher l'historique des actions
        
        return $this->render('profile/staff/_staff_history.html.twig', [
            'user' => $this->userRepository->getUser($this->getUser())
        ]);
    }
    
    // Route pour générer un rapport d'activité
    #[Route('/staff/report', name: 'app_staff_report')]
    public function activityReport(): Response
    {
        // Fonction à implémenter pour générer un rapport d'activité
        
        return $this->render('profile/staff/_staff_report.html.twig', [
            'user' => $this->userRepository->getUser($this->getUser())
        ]);
    }


}
