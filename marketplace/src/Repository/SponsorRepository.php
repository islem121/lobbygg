<?php

namespace App\Repository;

use App\Entity\Sponsor;
use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    public function findAllQuery(): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery();
    }

    /**
     * Recherche des offres de sponsoring par nom de société ou description
     */
    public function searchSponsors(string $query, string $sort = 'id', string $direction = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.sponsor', 'u')
            ->addSelect('u');

        if (!empty($query)) {
            $qb->andWhere('s.nomSociete LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $validSorts = ['id', 'nomSociete', 'amount', 'createdAt'];
        if (in_array($sort, $validSorts)) {
            $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
            $qb->orderBy('s.' . $sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère le nombre de demandes par société
     */
    public function getRequestsCountByCompany(): array
    {
        // On compte le nombre d'OFFRES (les lignes du tableau) par nom de société
        return $this->createQueryBuilder('s')
            ->select('s.nomSociete as company, COUNT(s.id) as count')
            ->groupBy('s.nomSociete')
            ->getQuery()
            ->getResult();
    }
}
