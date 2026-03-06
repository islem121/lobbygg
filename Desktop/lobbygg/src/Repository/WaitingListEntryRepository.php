<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\User;
use App\Entity\WaitingListEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WaitingListEntry>
 */
class WaitingListEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaitingListEntry::class);
    }

    public function findOneByUserAndTournament(User $user, Tournament $tournament): ?WaitingListEntry
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.tournament = :tournament')
            ->setParameter('user', $user)
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNextForPromotion(Tournament $tournament): ?WaitingListEntry
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('w.position', 'ASC')
            ->addOrderBy('w.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return WaitingListEntry[]
     */
    public function findByTournamentOrdered(Tournament $tournament): array
    {
        return $this->createQueryBuilder('w')
            ->innerJoin('w.user', 'u')->addSelect('u')
            ->andWhere('w.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('w.position', 'ASC')
            ->addOrderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

