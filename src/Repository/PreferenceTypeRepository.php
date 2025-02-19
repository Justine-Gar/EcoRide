<?php

namespace App\Repository;

use App\Entity\PreferenceType;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PreferenceType>
 */
class PreferenceTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreferenceType::class);
    }

    /**
     * Créer un nouveau type de préférence
     */
    public function createPreferenceType(array $data): PreferenceType
    {
        $preferenceType = new PreferenceType();

        if (!isset($data['name'])) {
            throw new \InvalidArgumentException('Le nom est requis');
        }

        $preferenceType->setName($data['name']);
        $preferenceType->setSystem(isset($data['is_system']) ? $data['is_system'] : false);

        $this->_em->persist($preferenceType);
        $this->_em->flush();

        return $preferenceType;
    }

    /**
     * Récupérer tous les types de préférences système
     */
    public function findSystemPreferences(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.is_systeme = :isSystem')
            ->setParameter('isSystem', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer tous les types de préférences utilisateur
     */
    public function findUserPreferences(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.is_systeme = :isSystem')
            ->setParameter('isSystem', false)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver un type de préférence par son nom
     */
    public function findByName(string $name): ?PreferenceType
    {
        return $this->createQueryBuilder('p')
            ->where('p.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupérer les types de préférences par utilisateur
     */
    public function findPreferencesByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.userPreferences', 'up')
            ->where('up.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Mettre à jour un type de préférence
     */
    public function updatePreferenceType(PreferenceType $preferenceType, array $data): PreferenceType
    {
        if (isset($data['name'])) {
            $preferenceType->setName($data['name']);
        }

        if (isset($data['is_systeme'])) {
            $preferenceType->setSystem($data['is_system']);
        }

        $this->_em->flush();

        return $preferenceType;
    }

    /**
     * Supprimer un type de préférence
     */
    public function deletePreferenceType(PreferenceType $preferenceType): void
    {
        // Empêcher la suppression des préférences système
        if ($preferenceType->isSystem()) {
            throw new \InvalidArgumentException('Les préférences système ne peuvent pas être supprimées');
        }

        $this->_em->remove($preferenceType);
        $this->_em->flush();
    }

    /**
     * Vérifier si un type de préférence existe
     */
    public function preferenceTypeExists(string $name): bool
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id_preference_types)')
            ->where('p.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    
}
