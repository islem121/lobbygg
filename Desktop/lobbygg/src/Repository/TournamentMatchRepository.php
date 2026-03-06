<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentMatch;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentMatch>
 */
class TournamentMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentMatch::class);
    }

    /**
     * @return TournamentMatch[]
     */
    public function findActiveByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.playerOne', 'p1')->addSelect('p1')
            ->innerJoin('m.playerTwo', 'p2')->addSelect('p2')
            ->leftJoin('m.winner', 'w')->addSelect('w')
            ->andWhere('m.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('m.roundNumber', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TournamentMatch[]
     */
    public function findPendingByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.playerOne', 'p1')->addSelect('p1')
            ->innerJoin('m.playerTwo', 'p2')->addSelect('p2')
            ->andWhere('m.tournament = :tournament')
            ->andWhere('m.status = :status')
            ->setParameter('tournament', $tournament)
            ->setParameter('status', TournamentMatch::STATUS_PENDING)
            ->orderBy('m.roundNumber', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMaxRoundForTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COALESCE(MAX(m.roundNumber), 0)')
            ->andWhere('m.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return TournamentMatch[]
     */
    public function findByUserAndTournament(Tournament $tournament, User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.tournament = :tournament')
            ->andWhere('m.playerOne = :user OR m.playerTwo = :user')
            ->setParameter('tournament', $tournament)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function countHeadToHead(Tournament $tournament, User $first, User $second): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.tournament = :tournament')
            ->andWhere('(m.playerOne = :first AND m.playerTwo = :second) OR (m.playerOne = :second AND m.playerTwo = :first)')
            ->setParameter('tournament', $tournament)
            ->setParameter('first', $first)
            ->setParameter('second', $second)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
