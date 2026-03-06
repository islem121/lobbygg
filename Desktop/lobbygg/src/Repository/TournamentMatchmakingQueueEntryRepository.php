<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentMatchmakingQueueEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentMatchmakingQueueEntry>
 */
class TournamentMatchmakingQueueEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentMatchmakingQueueEntry::class);
    }

    public function findForTournamentAndUser(Tournament $tournament, User $user): ?TournamentMatchmakingQueueEntry
    {
        return $this->findOneBy(['tournament' => $tournament, 'user' => $user]);
    }

    /**
     * @return TournamentMatchmakingQueueEntry[]
     */
    public function findQueueOrdered(Tournament $tournament): array
    {
        return $this->createQueryBuilder('q')
            ->innerJoin('q.user', 'u')->addSelect('u')
            ->andWhere('q.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('q.queuedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countQueue(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
