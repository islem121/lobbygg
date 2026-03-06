<?php

namespace App\Repository;

use App\Entity\ExternalTournamentInterest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalTournamentInterest>
 */
class ExternalTournamentInterestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalTournamentInterest::class);
    }

    public function findOneByUserAndUrl(User $user, string $url): ?ExternalTournamentInterest
    {
        return $this->findOneBy([
            'user' => $user,
            'url' => trim($url),
        ]);
    }

    /**
     * @return ExternalTournamentInterest[]
     */
    public function findRecentWithUser(int $limit = 200): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.user', 'u')->addSelect('u')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
