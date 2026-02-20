<?php

namespace App\Repository;

use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 */
class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    /**
     * @return array<int, array{0: Tournament, participantsCount: string}>
     */
    public function findWithParticipantsCount(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.participations', 'tp')
            ->addSelect('COUNT(tp.id) AS participantsCount')
            ->groupBy('t.id')
            ->orderBy('t.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneWithParticipations(int $id): ?Tournament
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.participations', 'tp')
            ->addSelect('tp')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->orderBy('tp.registrationDate', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
