<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
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

}
