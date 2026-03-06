<?php

namespace App\Repository;

use App\Entity\LeaderboardEntry;
use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LeaderboardEntry>
 */
class LeaderboardEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeaderboardEntry::class);
    }

    /**
     * @return LeaderboardEntry[]
     */
    public function findByTournamentOrdered(Tournament $tournament): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.user', 'u')->addSelect('u')
            ->andWhere('l.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('l.rank', 'ASC')
            ->addOrderBy('l.points', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{
     *     userId: int,
     *     username: string,
     *     totalWins: string,
     *     tournamentsPlayed: string,
     *     totalPoints: string
     * }>
     */
    public function getGlobalLeaderboardRows(int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->select('u.id AS userId')
            ->addSelect('u.username AS username')
            ->addSelect('SUM(l.wins) AS totalWins')
            ->addSelect('COUNT(l.id) AS tournamentsPlayed')
            ->addSelect('SUM(l.points) AS totalPoints')
            ->innerJoin('l.user', 'u')
            ->groupBy('u.id')
            ->orderBy('totalPoints', 'DESC')
            ->addOrderBy('totalWins', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
