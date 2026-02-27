<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findAllNonAdminsQuery(): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role != :adminRole')
            ->setParameter('adminRole', User::ROLE_ADMIN)
            ->orderBy('u.id', 'DESC')
            ->getQuery();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function findAllNonAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role != :adminRole')
            ->setParameter('adminRole', User::ROLE_ADMIN)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des utilisateurs par nom d'utilisateur ou email
     */
    public function searchNonAdmins(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role != :adminRole')
            ->andWhere('u.username LIKE :query OR u.email LIKE :query')
            ->setParameter('adminRole', User::ROLE_ADMIN)
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}
