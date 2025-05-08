<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Carpool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
        
    }

    /**
     * Créer un nouvel avis
     */
    public function createReview(array $data, User $user): Review
    {
        $review = new Review();
        
        // Vérification des données requises
        if (!isset($data['comment']) || !isset($data['note'])) {
            throw new \InvalidArgumentException('Le commentaire et la note sont requis');
        }

        // Vérification de la note
        if ($data['note'] < 0 || $data['note'] > 5) {
            throw new \InvalidArgumentException('La note doit être comprise entre 0 et 5');
        }

        // Configuration de l'avis
        $review->setComment($data['comment']);
        $review->setNote($data['note']);
        $review->setStatut('attente'); 
        $review->setUser($user);

        $this->getEntityManager()->persist($review);
        $this->getEntityManager()->flush();

        return $review;
    }

    /**
     * Créer un nouvel signalement
     */
    public function createReport(array $data, User $user, Carpool $carpool): Review 
    {
        $review = new Review();

        //verifie si les donné sont la 
        if (!isset($data['report_type']) || !isset($data['description']) || !isset($data['severity'])) {
            throw new \InvalidArgumentException('le type, la description et mla gravité sont requis');
        }
        
        //Avis spécial
        $review->setComment($data['description']);
        $review->setNote((float)$data['severity']);
        $review->setStatut('signalé');
        $review->setUser($user);
        $review->setSender($user);
        $review->setRecipient($carpool->getUser());
        $review->setCarpool($carpool);

        $reportDetails = [
            'type' => $data['report_type'],
            'report' => true
        ];

        $reviewComment = json_encode($reportDetails) . "||" . $data['description'];
        $review->setComment($reviewComment);

        $this->getEntityManager()->persist($review);
        $this->getEntityManager()->flush();
        
        return $review;
    }

    /**
     * Supprimer un avis
     */
    public function deleteReview(Review $review): void
    {
        $this->getEntityManager()->remove($review);
        $this->getEntityManager()->flush();
    }


    /**
     * Modérer un avis (approuver ou rejeter)
     */
    public function moderateReview(Review $review, string $status): void
    {
        if (!in_array($status, ['approuvé', 'rejeté'])) {
            throw new \InvalidArgumentException('Statut invalide');
        }

        $review->setStatut($status);
        $this->getEntityManager()->flush();
    }

    /**
     * Modérer un report
     */
    public function moderateReport(Review $report, string $status): void
    {
        if (!in_array($status, ['validé', 'rejeté'])) {
            throw new \InvalidArgumentException('Statut invalide');
        }

        $report->setStatut($status);
    
        // Si le signalement est validé, gérer les crédits
        if ($status === 'validé') {
            $passager = $report->getSender();
            $conducteur = $report->getRecipient();
            $carpool = $report->getCarpool();
            
            // Remboursement au passager
            if ($passager) {
                $passagerRefund = $carpool->getCredits();
                $passager->setCredits($passager->getCredits() + $passagerRefund);
                
            }
            // Pénalité au conducteur
            if ($conducteur) {
                $conducteurPenalty = 10;
                $conducteur->setCredits($conducteur->getCredits() - $conducteurPenalty);
            }
        }
        
        //enregistre les modif
        $this->getEntityManager()->flush();
        //Supprime le signalement apres traitment
        $this->getEntityManager()->remove($report);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupérer tous les avis en attente de modération
     */
    public function findPendingReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.statut = :status')
            ->setParameter('status', 'attente')
            ->orderBy('r.id_review', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les avis approuvé d'un user
     */
    public function findApprovedReviewsByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.statut = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'approuvé')
            ->orderBy('r.id_review', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les stats des avis user
     */
    public function getReviewStats(): array
    {
        $qb = $this->createQueryBuilder('r');
        
        $stats = $qb->select('r.statut, COUNT(r.id_review) as count, AVG(r.note) as average_rating')
            ->groupBy('r.statut')
            ->getQuery()
            ->getResult();

        $formattedStats = [];
        foreach ($stats as $stat) {
            $formattedStats[$stat['statut']] = [
                'count' => $stat['count'],
                'average_rating' => round($stat['average_rating'] ?? 0, 1)
            ];
        }

        return $formattedStats;
    }

    /**
     * Mettre à jour un avis existant
     */
    public function updateReview(Review $review, array $data): Review
    {
        if (isset($data['comment'])) {
            $review->setComment($data['comment']);
        }

        if (isset($data['note'])) {
            if ($data['note'] < 0 || $data['note'] > 5) {
                throw new \InvalidArgumentException('La note doit être comprise entre 0 et 5');
            }
            $review->setNote($data['note']);
        }

        // Après modification, l'avis repasse en statut "attente"
        $review->setStatut('attente');

        $this->getEntityManager()->flush();

        return $review;
    }
    
    /**
     * Compter le nombre d'avis d'un utilisateur
     */
    public function countReviews(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id_review)')
            ->andWhere('r.user = :user')
            ->andWhere('r.statut = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'publié')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtenir la note moyenne d'un utilisateur
     */
    public function getAverageDriverRating(User $user): ?float
    {
        try {
            return $this->createQueryBuilder('r')
                ->select('AVG(r.note) as average')
                ->andWhere('r.recipient = :user')
                ->andWhere('r.statut = :status')
                ->join('r.carpool', 'c')
                ->andWhere('c.user = :user')  // L'utilisateur est conducteur du covoiturage
                ->setParameter('user', $user)
                ->setParameter('status', 'publié')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
