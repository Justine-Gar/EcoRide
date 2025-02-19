<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 */
class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    // Create et Update
    public function save(Car $car, bool $flush = false) : void
    {
        $this->getEntityManager()->persist($car);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Delete
    public function remove(Car $car, bool $flush = false): void
    {
        $this->getEntityManager()->remove($car);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Trouver une voiture par son ID
    public function findCarOneById(int $id): ?Car
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id_cars = id')
            ->setParameter('id',$id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Trouver tout les voitures d'un user
    public function findCarsByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id_cars', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
