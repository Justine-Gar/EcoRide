<?php

namespace App\Repository;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    //Role de base
    private const BASIC_ROLES = ['Passager', 'Conducteur'];
    //Role administratif
    private const ADMIN_ROLES = ['Administrateur', 'Staff'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    //Sauvegarder un role
    public function save(Role $role): void
    {
        $this->_em->persist($role);
        $this->_em->flush();
    }

    //Supprimer un role
    public function remove(Role $role): void
    {
        if (in_array($role->getNameRole(), self::ADMIN_ROLES)) {
            throw new \Exception('Ce rôle administratif ne peut pas être supprimé');
        }
        
        $this->_em->remove($role);
        $this->_em->flush();
    }

    //trouver un role par son nom
    public function findByName(string $roleName): ?Role
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name_role = :name')
            ->setParameter('name', $roleName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //récupere les roles de bas
    public function findBasicRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.name_role IN (:roles)')
            ->setParameter('roles', self::BASIC_ROLES)
            ->getQuery()
            ->getResult();
    }

    //recupere les user d'une role spécifique
    public function findUsersByRole(string $roleName): array
    {
        return $this->createQueryBuilder('r')
            ->select('u')
            ->join('r.users', 'u')
            ->where('r.name_role = :role')
            ->setParameter('role', $roleName)
            ->getQuery()
            ->getResult();
    }

    //définir le role principal d'un user, si les 2 devient un passeur
    public function setUserMainRole(User $user, string $roleName): void
    {
        // Vérifie si le rôle est un rôle de base valide
        if (!in_array($roleName, self::BASIC_ROLES)) {
            throw new \InvalidArgumentException("Le rôle doit être 'Passager' ou 'Conducteur'");
        }

        // Récupère le rôle demandé
        $role = $this->findByName($roleName);
        if (!$role) {
            throw new \RuntimeException("Rôle non trouvé");
        }

        // Vérifie si l'utilisateur a déjà l'autre rôle de base
        $hasOtherBasicRole = false;
        foreach (self::BASIC_ROLES as $basicRole) {
            if ($basicRole !== $roleName && $user->hasRole($this->findByName($basicRole))) {
                $hasOtherBasicRole = true;
                break;
            }
        }

        // Ajoute le nouveau rôle s'il ne l'a pas déjà
        if (!$user->hasRole($role)) {
            $user->addRole($role);
        }

        // Si l'utilisateur a les deux rôles de base, ajoute le rôle Passeur
        if ($hasOtherBasicRole) {
            $passeurRole = $this->findByName('Passeur');
            if ($passeurRole && !$user->hasRole($passeurRole)) {
                $user->addRole($passeurRole);
            }
        }

        // Sauvegarde les changements
        $this->_em->persist($user);
        $this->_em->flush();
    }

    //Vérifie si role et role administratif
    public function isAdminRole(string $roleName): bool
    {
        return in_array($roleName, self::ADMIN_ROLES);
    }

    //Vérifie si role est un passeur
    public function isPasseur(User $user): bool
    {
        $hasAllBasicRoles = true;
        foreach (self::BASIC_ROLES as $roleName) {
            $role = $this->findByName($roleName);
            if (!$role || !$user->hasRole($role)) {
                $hasAllBasicRoles = false;
                break;
            }
        }
        return $hasAllBasicRoles;
    }
}
