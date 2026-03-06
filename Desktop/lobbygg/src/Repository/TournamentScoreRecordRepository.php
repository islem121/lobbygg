<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentScoreRecord;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentScoreRecord>
 */
class TournamentScoreRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentScoreRecord::class);
    }

    /**
     * @return TournamentScoreRecord[]
     */
    public function findByTournamentOrdered(Tournament $tournament): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')->addSelect('u')
            ->andWhere('s.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('s.score', 'DESC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForTournamentAndUser(Tournament $tournament, User $user): ?TournamentScoreRecord
    {
        return $this->findOneBy(['tournament' => $tournament, 'user' => $user]);
    }
}

