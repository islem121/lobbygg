<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * @return Document[] Returns an array of Document objects for a specific sponsor
     */
    public function findBySponsor($sponsor): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.offer', 'o')
            ->andWhere('o.sponsor = :sponsor')
            ->setParameter('sponsor', $sponsor)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
