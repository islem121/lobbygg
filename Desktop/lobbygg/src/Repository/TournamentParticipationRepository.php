<?php

namespace App\Repository;

use App\Entity\TournamentParticipation;
use App\Entity\User;
use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentParticipation>
 */
class TournamentParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentParticipation::class);
    }

    public function findOneByUserAndTournament(User $user, Tournament $tournament): ?TournamentParticipation
    {
        return $this->createQueryBuilder('tp')
            ->andWhere('tp.user = :user')
            ->andWhere('tp.tournament = :tournament')
            ->setParameter('user', $user)
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('tp')
            ->select('COUNT(tp.id)')
            ->andWhere('tp.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
