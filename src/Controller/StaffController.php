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
use Symfony\Bundle\TwigBundle\DependencyInjection\Compiler\TwigEnvironmentPass;

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

        $waitReviews = $this->reviewRepository->findPendingReviews();
        //récupérer les signalement
        $reportReviews = $this->reviewRepository->findReport();
        //Récupérer les statistque des avis
        $reviewStats = $this->reviewRepository->getReviewStats();

        //Nombre d'avis en attente
        $waitCount = count($waitReviews);

        //Nombre d'avis traité (approuvé et rejetés)
        $reviewsCount = ($reviewStats['publié']['count'] ?? 0) + ($reviewStats['rejeté']['count'] ?? 0);
        $reviewsCountReport = ($reviewStats['signalé']['count']);

        // Rend la vue staff
        return $this->render('profile/staff.html.twig', [
            'user' => $user,
            'pendingReviews' => $waitReviews,
            'reportReviews' => $reportReviews,
            'pendingCount' => $waitCount,
            'processedCount' => $reviewsCount,
            'reportedCount' => $reviewsCountReport,
        ]);
    }
    

    // Route pour voir les avis
    #[Route('/staff/reviews', name: 'app_staff_reviews')]
    #[IsGranted('ROLE_STAFF')]
    public function reviewsPartial(): Response 
    {
        $pendingReviews = $this->reviewRepository->findPendingReviews();
        return $this->render('profile/staff/_staff_reviews.html.twig', [
        'pendingReviews' => $pendingReviews
        ]);
    }

    // Route pour approuver un avis
    #[Route('/staff/reviews/{id}/approve', name: 'app_staff_approve_review', methods: ['POST'])]
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
                return new JsonResponse([
                    'success' => true,
                    'flashMessage' => 'L\'avis a été approuvé avec succès.',
                    'flashType' => 'success'
                ]);
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
    #[Route('/staff/reviews/{id}/reject', name: 'app_staff_reject_review', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function rejectReview(Review $review, Request $request): Response
    {
        try {
            // Rejeter l'avis
            $this->reviewRepository->moderateReview($review, 'rejeté');
            
            $this->addFlash('success', 'L\'avis a été rejeté.');
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'flashMessage' => 'L\'avis a été rejeté.',
                    'flashType' => 'success'
                ]);
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

    //Route pour afficher le détails d'un avis
    #[Route('/staff/reviews/{id}/details', name: 'app_staff_reviews_details')]
    #[IsGranted('ROLE_STAFF')]
    public function reviewDetails(Review $review): Response
    {
        $html = $this->renderView('profile/staff/_staff_reviews_details.html.twig', [
            'review' => $review
        ]);
        
        return new Response($html);
    }



    //Route pour voir les signalements
    #[Route('/staff/reports', name: 'app_staff_reports')]
    #[IsGranted('ROLE_STAFF')]
    public function reportsPartial(): Response
    {
        $reportReviews = $this->reviewRepository->findReport();
        return $this->render('profile/staff/_staff_reports.html.twig', [
            'reportReviews' => $reportReviews
        ]);
    }

    // Route pour marquer un signalement comme résolu
    #[Route('/staff/reports/{id}/resolve', name: 'app_staff_resolve_report', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function resolveReport(Request $request, Review $report): Response
    {
        try {
            $originalReview = $report->getComment();

            //Nettoyage de l'avis
            $cleanedReview = preg_replace('/\{.*?\}\|\|/', '', $originalReview);
            if ($cleanedReview === $originalReview) {
                $cleanedReview = preg_replace('/\{.*?\}/', '', $originalReview);
            }
            $cleanedReview = trim($cleanedReview); 

            $report->setStatut('publié');
            $report->setComment($cleanedReview);

            $this->entityManager->persist($report);
            $this->entityManager->flush();

            $driver = $report->getRecipient();
            if ($driver) {
                $this->userRepository->updateDriverRating($driver);
            }

            $this->addFlash('success', 'Le signalement a été résolu et le conducteur a reçu une pénalité.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'flashMessage' => 'Le signalement a été résolu et le conducteur a reçu une pénalité.',
                    'flashType' => 'success'
                ]);
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
    
    //Route pour afficher le détails d'un signalement
    #[Route('/staff/reports/{id}/details', name: 'app_staff_reports_details')]
    #[IsGranted('ROLE_STAFF')]
    public function reportDetails(Review $report): Response
    {
        if (!$report) {
            throw $this->createNotFoundException('Signalement non trouvé');
        }

        $html = $this->renderView('profile/staff/_staff_reports_details.html.twig', [
            'report' => $report
        ]);
        
        return new Response($html);
    }

    //Route pour signaler user à l'administrateur
    #[Route('/staff/reports/{id}/danger', name: 'app_staff_reports_danger', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function dangerReport(Request $request, Review $report): Response
    {
        try {

            $report->setStatut('danger');

            $this->entityManager->persist($report);
            $this->entityManager->flush();

            //Notification admin ?

            $this->addFlash('success', 'Le signalement a été envoyé a l\'administrateur.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'flashMessage' => 'Le signalement a été envoyé a l\'administrateur.',
                    'flashType' => 'success'
                ]);
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

}
