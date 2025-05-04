<?php

namespace App\Repository;

use App\Entity\Carpool;
use App\Entity\User;
use App\Entity\Car;
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
    public function createCarpool(User $user, array $data): Carpool
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

        // Associer une voiture si fournie
        if (isset($data['car_id'])) {
            $carRepository = $this->getEntityManager()->getRepository(Car::class);
            $car = $carRepository->find($data['car_id']);
            if ($car) {
                // Vérifier que la voiture appartient à l'utilisateur
                if ($car->getUser()->getIdUser() !== $user->getIdUser()) {
                    throw new \Exception('Ce véhicule ne vous appartient pas');
                }
                
                // Vérifier que la voiture a assez de places
                if ($car->getNbrPlaces() < $data['nbr_places']) {
                    throw new \Exception('Le véhicule sélectionné n\'a pas assez de places');
                }
            }
        }

        
        // Définir les données obligatoires
        if (!isset($data['date_start']) || !isset($data['date_reach']) || 
        !isset($data['location_start']) || !isset($data['location_reach']) ||
        !isset($data['hour_start']) || !isset($data['hour_reach']) ||
        !isset($data['nbr_places']) || !isset($data['credits'])) {
            throw new \Exception('Toutes les informations requises doivent être fournies');
        }

        // Si des préférences sont fournies 
        if (isset($data['preferences'])) {
            $carpool->setPreferences($data['preferences']);
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
        $carpool->setDateStart(new \DateTime($data['date_start']));
        $carpool->setLocationStart($data['location_start']);
        $carpool->setHourStart(new \DateTime($data['hour_start']));
        $carpool->setDateReach(new \DateTime($data['date_reach']));
        $carpool->setLocationReach($data['location_reach']);
        $carpool->setHourReach(new \DateTime($data['hour_reach']));
        $carpool->setNbrPlaces((int)$data['nbr_places']);
        $carpool->setCredits((int)$data['credits']);
        $carpool->setStatut(Carpool::STATUS_WAITING);

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
            ->setParameter('status', Carpool::STATUS_WAITING)
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
     * Vérifie si un user participe deja à un covoitrage en actif ou attente
     */
    public function findActiveOrWaitingParticipation(User $user, ?int $excludeCarpoolId = null): ?array
    {
        $qb = $this->createQueryBuilder('c')
        ->join('c.passengers', 'p')
        ->where('p.id_user = :userId')
        ->andWhere('c.statut IN (:statuses)')
        ->setParameter('userId', $user->getIdUser())
        ->setParameter('statuses', [Carpool::STATUS_ACTIVE, Carpool::STATUS_WAITING])
        ->orderBy('c.date_start', 'ASC');
    
        // Si un ID de covoiturage est fourni, l'exclure de la recherche
        if ($excludeCarpoolId !== null) {
            $qb->andWhere('c.id_carpool != :excludeId')
            ->setParameter('excludeId', $excludeCarpoolId);
        }
        
        // Limiter à un seul résultat
        $qb->setMaxResults(1);
        
        $result = $qb->getQuery()->getResult();
        
        // Retourner le premier covoiturage trouvé, ou null
        return !empty($result) ? $result[0] : null;
    }

    /**
    * Vérifie si un utilisateur peut rejoindre un covoiturage
    */
    public function canUserJoinCarpool(User $user, Carpool $carpool): array
    {
        // Vérifier si l'utilisateur est le conducteur
        if ($carpool->getUser() === $user) {
            return [
                'can_join' => false,
                'message' => 'Vous ne pouvez pas rejoindre votre propre covoiturage.'
            ];
        }
        
        // Vérifier si l'utilisateur est déjà inscrit à ce covoiturage
        if ($carpool->getPassengers()->contains($user)) {
            return [
                'can_join' => false,
                'message' => 'Vous êtes déjà inscrit à ce covoiturage.'
            ];
        }
        
        // Vérifier s'il reste des places disponibles
        if ($carpool->getPassengers()->count() >= $carpool->getNbrPlaces()) {
            return [
                'can_join' => false,
                'message' => 'Il n\'y a plus de places disponibles pour ce covoiturage.'
            ];
        }
        
        // Vérifier si l'utilisateur participe déjà à un autre covoiturage
        $existingParticipation = $this->findActiveOrWaitingParticipation($user, $carpool->getIdCarpool());
        if ($existingParticipation !== null) {
            return [
                'can_join' => false,
                'message' => "Vous êtes déjà inscrit à un covoiturage. Vous ne pouvez rejoindre qu'un seul covoiturage à la fois.",
                'existing_carpool' => $existingParticipation
            ];
        }

        // Vérifier si l'utilisateur a assez de crédits
        if ($user->getCredits() < $carpool->getCredits()) {
            return [
                'can_join' => false,
                'message' => "Vous n'avez pas assez de crédits pour rejoindre ce covoiturage.",
                'credits_required' => $carpool->getCredits(),
                'user_credits' => $user->getCredits()
            ];
        }
        
        // Toutes les vérifications ont été passées
        return [
            'can_join' => true,
            'message' => ''
        ];
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
     * Démarrer un covoiturage
     */
    public function startCarpool(Carpool $carpool): void
    {
        if (!$carpool->isWaitingCarpool()) {
            throw new \LogicException('Ce covoiturage ne peut etre démarer car il n\'est pas en attente');
        }
        $carpool->setStatut(Carpool::STATUS_ACTIVE);
        $this->save($carpool, true);
    }

    /**
     * Terminer un covoiturage
     */
    public function finishCarpool(Carpool $carpool): void
    {
        if (!$carpool->isActiveCarpool()) {
            throw new \LogicException(' Ce covoiturage ne peut etre terminé car il n\'est pas actif');
        }
        $carpool->setStatut(Carpool::STATUS_COMPLETED);
        $this->save($carpool, true);
    }

    /**
     * Annuler un covoiturage
     */
    public function cancelCarpool(Carpool $carpool): void
    {
        if ($carpool->isCompletedCarpool()) {
            throw new \LogicException('Ce covoiturage ne peut pas être annulé car il est déjà terminé');
        }
        $carpool->setStatut(Carpool::STATUS_CANCELED);
        $this->save($carpool, true); 
    }

    /**
    * Rechercher des covoiturages par termes simples
    */
    public function search(?string $depart = null, ?string $arrivee = null, ?string $date = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.statut = :status')
            ->setParameter('status', Carpool::STATUS_WAITING);
        
        if ($depart) {
            $qb->andWhere('c.location_start LIKE :depart')
            ->setParameter('depart', '%' . $depart . '%');
        }
        
        if ($arrivee) {
            $qb->andWhere('c.location_reach LIKE :arrivee')
            ->setParameter('arrivee', '%' . $arrivee . '%');
        }
        
        if ($date) {
            $qb->andWhere('c.date_start = :date')
            ->setParameter('date', new \DateTime($date));
        }
        
        $qb->orderBy('c.date_start', 'ASC')
        ->addOrderBy('c.hour_start', 'ASC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Rechercher des covoiturages par critères
     */
    public function searchCarpools(array $criteria): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.statut = :status')
            ->setParameter('status', Carpool::STATUS_WAITING);

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
    

    /**
     * Recupérer les covoiturage "actifs" user = conducteur
     */
    public function findActiveCarpoolsAsDriver(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', Carpool::STATUS_ACTIVE)
            ->orderBy('c.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupérer les covoiturage "terminé" user = conducteur
     */
    public function findCompletedCarpoolsAsDriver(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', Carpool::STATUS_COMPLETED)
            ->orderBy('c.date_start', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les covoiturage "attente" user = conducteur
     */
    public function findWaitingCarpoolAsDriver(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', Carpool::STATUS_WAITING)
            ->orderBy('c.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Recupérer les covoiturage "annuler" user = conducteur
     */
    public function findCanceledCarpoolsAsDriver(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', Carpool::STATUS_CANCELED)
            ->orderBy('c.date_start', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupérer les covoiturage "actifs" user = passager
     */
    public function findActiveCarpoolsAsPassenger(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.passengers', 'p')
            ->where('p.id_user = :userId')
            ->andWhere('c.statut = :statut')
            ->setParameter('userId', $user->getIdUser())
            ->setParameter('statut', Carpool::STATUS_ACTIVE)
            ->orderBy('c.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupérer les covoiturage "terminé" user = passager
     */
    public function findCompletedCarpoolsAsPassenger(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.passengers', 'p')
            ->where('p.id_user = :userId')
            ->andWhere('c.statut = :statut')
            ->setParameter('userId', $user->getIdUser())
            ->setParameter('statut', Carpool::STATUS_COMPLETED)
            ->orderBy('c.date_start', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupérer les covoiturage "attente" user = passager
     */
    public function findWaitingCarpoolsAsPassenger(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.passengers', 'p')
            ->where('p.id_user = :userId')
            ->andWhere('c.statut = :statut')
            ->setParameter('userId', $user->getIdUser())
            ->setParameter('statut', Carpool::STATUS_WAITING)
            ->orderBy('c.date_start', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupérer les covoiturage "annulés" user = passager
     */
    public function findCanceledCarpoolsAsPassenger(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.passengers', 'p')
            ->where('p.id_user = :userId')
            ->andWhere('c.statut = :statut')
            ->setParameter('userId', $user->getIdUser())
            ->setParameter('statut', Carpool::STATUS_CANCELED)
            ->orderBy('c.date_start', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
