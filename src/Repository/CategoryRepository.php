<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return Category[] Returns an array of Category objects matching the search query and sort
     */
    public function searchByName(string $query = null, string $sortField = 'id', string $sortDirection = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($query) {
            $qb->andWhere('c.name LIKE :query OR c.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Validate sort field
        $allowedSortFields = ['id', 'name'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }

        // Validate sort direction
        $sortDirection = strtoupper($sortDirection);
        if (!in_array($sortDirection, ['ASC', 'DESC'])) {
            $sortDirection = 'ASC';
        }

        return $qb->orderBy('c.' . $sortField, $sortDirection)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
