<?php

namespace App\Repository;

use App\Entity\Carpool;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;


class CarpoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carpool::class);
    }

    /**
     * Creer un nouveau covoit
     */
    public function createCarpool(User $user, array $date): Carpool
    {
        // Vérifier si l'utilisateur a le rôle conducteur
        if (!$user->hasRoleByName('Conducteur')) {
            throw new \Exception('Vous devez être conducteur pour créer un covoiturage');
        }

        // Vérifier si l'utilisateur a une voiture
        if ($user->getCars()->isEmpty()) {
            throw new \Exception('Vous devez avoir une voiture enregistrée pour créer un covoiturage');
        }

        // Créer le covoiturage
        $carpool = new Carpool();
        $carpool->setUser($user);

        // Définir les données obligatoires
        if (!isset($data['date_start']) || !isset($data['date_reach']) || 
            !isset($data['location_start']) || !isset($data['location_reach']) ||
            !isset($data['hour_start']) || !isset($data['hour_reach']) ||
            !isset($data['nbr_places']) || !isset($data['credits'])) {
            throw new \Exception('Toutes les informations requises doivent être fournies');
        }

        // Vérifier la cohérence des dates
        $dateStart = new \DateTime($data['date_start'] . ' ' . $data['hour_start']);
        $dateReach = new \DateTime($data['date_reach'] . ' ' . $data['hour_reach']);

        if ($dateStart >= $dateReach) {
            throw new \Exception('La date d\'arrivée doit être postérieure à la date de départ');
        }

        if ($dateStart < new \DateTime()) {
            throw new \Exception('La date de départ ne peut pas être dans le passé');
        }

        // Définir toutes les propriétés
        $carpool->setDateStart($dateStart);
        $carpool->setDateReach($dateReach);
        $carpool->setLocationStart($data['location_start']);
        $carpool->setLocationReach($data['location_reach']);
        $carpool->setHourStart(new \DateTime($data['hour_start']));
        $carpool->setHourReach(new \DateTime($data['hour_reach']));
        $carpool->setNbrPlaces($data['nbr_places']);
        $carpool->setCredits($data['credits']);
        $carpool->setStatut('active');

        // Coordonnées géographiques si fournies
        if (isset($data['lat_start']) && isset($data['lng_start'])) {
            $carpool->setLatStart($data['lat_start']);
            $carpool->setLngStart($data['lng_start']);
        }
        if (isset($data['lat_reach']) && isset($data['lng_reach'])) {
            $carpool->setLatReach($data['lat_reach']);
            $carpool->setLngReach($data['lng_reach']);
        }

        // Sauvegarder le covoiturage
        $this->save($carpool, true);

        return $carpool;
    }

    /**
     * Sauvegarder un covoit
     */
    public function save(Carpool $carpool, bool $flush = false): void
    {
        $this->getEntityManager()->persist($carpool);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprimer un covoit
     */
    public function remove(Carpool $carpool, bool $flush = false): void
    {
        $this->getEntityManager()->remove($carpool);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * Trouver un covoiturage disponible
     */
    public function findAvailableCarpools(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :status')
            ->andWhere('c.nbr_places > 0')
            ->andWhere('c.date_start >= :today')
            ->setParameter('status', 'active')
            ->setParameter('today', new DateTime('today'))
            ->orderBy('c.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les covoiturage auxquels un user participe
     */
    public function findUserParticipations(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.passengers', 'p')
            ->where('p.id_user = :userId')
            ->setParameter('userId', $user->getIdUser())
            ->orderBy('c.date_start', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Mettre à jour le statut d'un covoiturage
     */
    public function updateStatus(Carpool $carpool, string $status): void
    {
        $carpool->setStatut($status);
        $this->save($carpool, true);
    }

    /**
     * Rechercher des covoiturages par critères
     */
    public function searchCarpools(array $criteria): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.statut = :status')
            ->setParameter('status', 'active');

        if (isset($criteria['date_start'])) {
            $qb->andWhere('c.date_start = :date_start')
                ->setParameter('date_start', $criteria['date_start']);
        }

        if (isset($criteria['location_start'])) {
            $qb->andWhere('c.location_start LIKE :location_start')
                ->setParameter('location_start', '%' . $criteria['location_start'] . '%');
        }

        if (isset($criteria['location_reach'])) {
            $qb->andWhere('c.location_reach LIKE :location_reach')
                ->setParameter('location_reach', '%' . $criteria['location_reach'] . '%');
        }

        if (isset($criteria['min_places'])) {
            $qb->andWhere('c.nbr_places >= :min_places')
                ->setParameter('min_places', $criteria['min_places']);
        }

        return $qb->orderBy('c.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
}
