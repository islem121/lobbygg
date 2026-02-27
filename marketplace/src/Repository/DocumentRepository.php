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

    public function findAllQuery(): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery();
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

    /**
     * Recherche et tri des documents (demandes)
     */
    public function searchDocuments(string $query, string $sort = 'id', string $direction = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'u')
            ->leftJoin('d.offer', 's')
            ->addSelect('u', 's');

        if (!empty($query)) {
            $qb->andWhere('d.nomClient LIKE :query OR d.emailClient LIKE :query OR s.nomSociete LIKE :query OR d.status LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $validSorts = ['id', 'nomClient', 'status', 'createdAt'];
        if (in_array($sort, $validSorts)) {
            $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
            $qb->orderBy('d.' . $sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques des demandes par statut (en attente, accepté, refusé)
     */
    public function getCountByStatus(): array
    {
        return $this->createQueryBuilder('d')
            ->select('d.status, COUNT(d.id) as count')
            ->groupBy('d.status')
            ->getQuery()
            ->getResult();
    }
}
