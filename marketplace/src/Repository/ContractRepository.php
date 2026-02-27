<?php

namespace App\Repository;

use App\Entity\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    public function findAllQuery(): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery();
    }

    /**
     * Recherche et tri des contrats
     */
    public function searchContracts(string $query, string $sort = 'id', string $direction = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.sponsor', 's')
            ->leftJoin('c.client', 'cl')
            ->leftJoin('c.request', 'd')
            ->leftJoin('d.offer', 'o')
            ->addSelect('s', 'cl', 'd', 'o');

        if (!empty($query)) {
            $qb->andWhere('s.username LIKE :query OR cl.username LIKE :query OR o.nomSociete LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $validSorts = ['id', 'createdAt'];
        if (in_array($sort, $validSorts)) {
            $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
            $qb->orderBy('c.' . $sort, $direction);
        } elseif ($sort === 'nomSociete') {
            $qb->orderBy('o.nomSociete', $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques des contrats par société
     */
    public function getCountByCompany(): array
    {
        return $this->createQueryBuilder('c')
            ->select('o.nomSociete as company, COUNT(c.id) as count')
            ->join('c.request', 'd')
            ->join('d.offer', 'o')
            ->groupBy('o.nomSociete')
            ->getQuery()
            ->getResult();
    }
}
