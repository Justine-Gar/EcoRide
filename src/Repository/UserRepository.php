<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Role;
use App\Repository\ReviewRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    private ReviewRepository $reviewRepository;

    public function __construct(ManagerRegistry $registry, ReviewRepository $reviewRepository)
    {
        parent::__construct($registry, User::class);
        $this->reviewRepository = $reviewRepository;
    }

    /**
     * Créer ou mettre à jour un utilisateur
     */
    public function save(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function remove(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver tous les utilisateurs
     */
    public function findAllUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.id_user', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver un utilisateur par son ID
     */
    public function findOneById(int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id_user = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver un utilisateur par son email
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère un utilisateur complet avec toutes ses données
     */
    public function getUser(UserInterface $user): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $user->getUserIdentifier())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule la note moyenne d'un User
     */
    private function calculateRating(User $user): float
    {
        $reviews = $user->getReview();
        if ($reviews->isEmpty()) {
            return 0.0;
        }

        $total = 0;
        $count = 0;
        foreach ($reviews as $review) {
            if ($review->getStatut() === 'approved') {
                $total += $review->getNote();
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 1) : 0.0;
    }

    /**
     * Met a jour la note moyenne d'un User
     */
    public function updateRating(User $user): void
    {
        $rating = $this->calculateRating($user);
        $user->setRating($rating);
        $this->save($user, true);
    }

    
    /**
     * Calcule la note moyenne d'un User en tant que conducteur
     */
    private function calculateDriverRating(User $user): float
    {
        // On récupère les avis où l'utilisateur est le destinataire
        $recipientReviews = $user->getRecipientReviews();
        if ($recipientReviews->isEmpty()) {
            return 0.0;
        }

        $total = 0;
        $count = 0;
        
        foreach ($recipientReviews as $review) {
            // Vérifier si l'avis est approuvé/publié
            if ($review->getStatut() === 'publié') {
                // Vérifier si l'avis concerne un covoiturage où l'utilisateur était conducteur
                $carpool = $review->getCarpool();
                if ($carpool && $carpool->getUser() === $user) {
                    $total += floatval($review->getNote());
                    $count++;
                }
            }
        }

        return $count > 0 ? round($total / $count, 1) : 0.0;
    }

    /**
     * Met à jour la note moyenne d'un User en tant que conducteur
     */
    public function updateDriverRating(User $user): void
    {
        $rating = $this->calculateDriverRating($user);
        $user->setRating($rating);
        $this->save($user, true);
    }

    /**
     * Met à jour les crédits d'un user
     */
    public function updateCredits(User $user, int $amont): void
    {   
        //Recupere les crédit  + le montant a ajouter (peut etre négatif)
        $newCredits = $user->getCredits() + $amont;
        //Met a jour le crédit et sauvegarde les modifs
        $user->setCredits($newCredits);
        $this->save($user, true);
    }


    /**
     * Vérifie si l'utilisateur possède un rôle spécifique
     */
    public function hasRole(User $user, string $roleName): bool
    {
        return $user->hasRoleByName($roleName);
    }
    /**
     * Vérifie si l'utilisateur est un passager
     */
    public function isPassenger(User $user): bool
    {
        return $this->hasRole($user, 'Passager');
    }
    /**
     * Vérifie si l'utilisateur est un conducteur
     */
    public function isDriver(User $user): bool
    {
        return $this->hasRole($user, 'Conducteur');
    }
}
