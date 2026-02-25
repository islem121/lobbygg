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
}
