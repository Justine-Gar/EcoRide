<?php

namespace App\Repository;

use App\Entity\UserPreference;
use App\Entity\User;
use App\Entity\PreferenceType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPreference>
 */
class UserPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreference::class);
    }

    /**
     * Créer ou mettre à jour une préférence utilisateur
     */
    public function setUserPreference(User $user, PreferenceType $preferenceType, string $value): UserPreference
    {
        // Vérifier si la préférence existe déjà
        $preference = $this->findOneBy([
            'user' => $user,
            'preferenceType' => $preferenceType
        ]);

        if (!$preference) {
            $preference = new UserPreference();
            $preference->setUser($user);
            $preference->setPreferenceType($preferenceType);
        }

        $preference->setChooseValue($value);

        $this->_em->persist($preference);
        $this->_em->flush();

        return $preference;
    }


    /**
     * Récupérer toutes les préférences d'un utilisateur
     */
    public function findUserPreferences(User $user): array
    {
        return $this->createQueryBuilder('up')
            ->where('up.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer une préférence spécifique d'un utilisateur
     */
    public function getUserPreferenceValue(User $user, PreferenceType $preferenceType): ?string
    {
        $preference = $this->findOneBy([
            'user' => $user,
            'preferenceType' => $preferenceType
        ]);

        return $preference ? $preference->getChooseValue() : null;
    }

    /**
     * Récupérer les préférences personnalisées d'un utilisateur
     */
    public function findUserCustomPreferences(User $user): array
    {
        return $this->createQueryBuilder('up')
            ->join('up.preferenceType', 'pt')
            ->where('pt.is_systeme = :isSystem')
            ->andWhere('up.user = :user')
            ->setParameter('isSystem', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprimer une préférence utilisateur
     */
    public function deleteUserPreference(User $user, PreferenceType $preferenceType): void
    {
        $preference = $this->findOneBy([
            'user' => $user,
            'preferenceType' => $preferenceType
        ]);

        if ($preference) {
            $this->_em->remove($preference);
            $this->_em->flush();
        }
    }

    /**
     * Réinitialiser toutes les préférences d'un utilisateur
     */
    public function resetUserPreferences(User $user): void
    {
        $preferences = $this->findUserPreferences($user);
        
        foreach ($preferences as $preference) {
            $this->_em->remove($preference);
        }
        
        $this->_em->flush();
    }

    /**
     * Vérifie si un utilisateur possède une préférence spécifique ou n'importe quelle préférence
     */
    public function userHasPreference(User $user, ?int $preferenceTypeId = null): bool
    {
        $qb = $this->createQueryBuilder('up')
            ->where('up.user = :user')
            ->setParameter('user', $user);
        
        if ($preferenceTypeId !== null) {
            $qb->andWhere('up.preferenceType = :prefId')
            ->setParameter('prefId', $preferenceTypeId);
        }
        
        $count = $qb->select('COUNT(up.id_user_preference)')
                ->getQuery()
                ->getSingleScalarResult();
        
        return $count > 0;
    }

    /**
     * Calcule total des crédit de tout les utilisateurs
     */
    public function getTotalSystemCredits(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('SUM(u.credits)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
