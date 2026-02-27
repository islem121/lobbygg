<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voucher>
 */
class VoucherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voucher::class);
    }

    public function findValidForUserAndTournament(User $user, Tournament $tournament): ?Voucher
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->andWhere('v.tournament = :tournament')
            ->andWhere('v.usedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('tournament', $tournament)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAnyForUserAndTournament(User $user, Tournament $tournament): ?Voucher
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->andWhere('v.tournament = :tournament')
            ->setParameter('user', $user)
            ->setParameter('tournament', $tournament)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{
     *     tournamentId: int,
     *     tournamentTitle: string,
     *     soldVouchers: string,
     *     entryFee: string,
     *     maxPlayers: int
     * }>
     */
    public function getSalesSummaryByTournament(?int $tournamentId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->select('t.id AS tournamentId')
            ->addSelect('t.title AS tournamentTitle')
            ->addSelect('COUNT(v.id) AS soldVouchers')
            ->addSelect('t.entryFee AS entryFee')
            ->addSelect('t.maxPlayers AS maxPlayers')
            ->innerJoin('v.tournament', 't')
            ->groupBy('t.id')
            ->orderBy('t.startDate', 'DESC');

        if ($tournamentId !== null) {
            $qb->andWhere('t.id = :tid')->setParameter('tid', $tournamentId);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
