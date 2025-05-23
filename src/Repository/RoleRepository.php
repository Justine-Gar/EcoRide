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

    //Vérifie si un user à un role spécifique
    private function userHasRole(User $user, string $roleName): bool
    {
        $role = $this->findByName($roleName);
        if (!$role) {
            return false;
        }
        
        return $user->hasRole($role);
    }

    //Ajoute un role a user
    private function addRoleToUser(User $user, Role $role): void
    {
        if (!$this->userHasRole($user, $role->getNameRole())) {
            $user->addRole($role);
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }
    }

    //Défini le role principal d'un user
    public function setUserMainRole(User $user, string $roleName): void
    {
        try {

            $basicRoles = ['Passager', 'Conducteur'];
    
            // Vérifie si le rôle est un rôle de base valide
            if (!in_array($roleName, $basicRoles)) {
                throw new \InvalidArgumentException("Le rôle doit être 'Passager' ou 'Conducteur'");
            }
    
            // Récupère le rôle demandé
            $role = $this->findByName($roleName);
            if (!$role) {
                throw new \RuntimeException("Rôle non trouvé: {$roleName}");
            }
    
            // Retire l'autre rôle de base (si présent)
            foreach ($basicRoles as $basicRole) {
                if ($basicRole !== $roleName) {
                    $otherRole = $this->findByName($basicRole);
                    if ($otherRole && $user->hasRole($otherRole)) {
                        $user->removeRole($otherRole);
                    }
                }
            }
    
            // Ajoute le nouveau rôle s'il ne l'a pas déjà
            if (!$this->userHasRole($user, $roleName)) {
                $this->addRoleToUser($user, $role);
            }
            
            // Sauvegarde les changements
            $this->getEntityManager()->flush();
        }
        catch (\Exception $e)
        {
            error_log("Erreur dans setUserMainRole: " . $e->getMessage());
            throw $e;
        }
    }
    
    //Vérifie si role et role administratif
    public function isAdminRole(string $roleName): bool
    {
        return in_array($roleName, self::ADMIN_ROLES);
    }

}
