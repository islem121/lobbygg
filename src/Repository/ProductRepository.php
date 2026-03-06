<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[] Returns an array of Product objects matching the voice criteria
     */
    public function searchByVoiceCriteria(?string $keyword, ?float $maxPrice): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($keyword) {
            $qb->andWhere('p.name LIKE :keyword OR p.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }

        if ($maxPrice) {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }
        
        return $qb->orderBy('p.price', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[] Returns an array of Product objects matching the search query and sort
     */
    public function searchByName(string $query = null, string $sortField = 'id', string $sortDirection = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($query) {
            $qb->andWhere('p.name LIKE :query OR p.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Validate sort field
        $allowedSortFields = ['id', 'name', 'price', 'stock', 'createdAt'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }

        // Validate sort direction
        $sortDirection = strtoupper($sortDirection);
        if (!in_array($sortDirection, ['ASC', 'DESC'])) {
            $sortDirection = 'ASC';
        }

        return $qb->orderBy('p.' . $sortField, $sortDirection)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
