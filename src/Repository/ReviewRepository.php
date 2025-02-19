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
        $review->setStatut('pending'); // Par défaut en attente de modération
        $review->setUser($user);

        $this->_em->persist($review);
        $this->_em->flush();

        return $review;
    }

    /**
     * Supprimer un avis
     */
    public function deleteReview(Review $review): void
    {
        $this->_em->remove($review);
        $this->_em->flush();
    }


    /**
     * Modérer un avis (approuver ou rejeter)
     */
    public function moderateReview(Review $review, string $status): void
    {
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new \InvalidArgumentException('Statut invalide');
        }

        $review->setStatut($status);
        $this->_em->flush();
    }

    /**
     * Récupérer tous les avis en attente de modération
     */
    public function findPendingReviews(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.statut = :status')
            ->setParameter('status', 'pending')
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
            ->setParameter('status', 'approved')
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

        // Après modification, l'avis repasse en statut "pending"
        $review->setStatut('pending');

        $this->_em->flush();

        return $review;
    }
    
}
