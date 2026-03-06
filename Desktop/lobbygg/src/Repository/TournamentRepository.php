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
    private const ALLOWED_SORT_FIELDS = [
        'title' => 't.title',
        'status' => 't.status',
        'startDate' => 't.startDate',
        'endDate' => 't.endDate',
        'maxPlayers' => 't.maxPlayers',
        'participantsCount' => 'participantsCount',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    /**
     * @return array<int, array{0: Tournament, participantsCount: string}>
     */
    public function findWithParticipantsCount(): array
    {
        return $this->findWithParticipantsCountFiltered();
    }

    /**
     * @return array<int, array{0: Tournament, participantsCount: string}>
     */
    public function findWithParticipantsCountFiltered(
        ?string $query = null,
        ?string $status = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        ?string $mode = null,
        string $sortBy = 'startDate',
        string $sortDir = 'asc',
        ?int $limit = null,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.participations', 'tp')
            ->addSelect('COUNT(tp.id) AS participantsCount')
            ->groupBy('t.id');

        if ($query !== null && $query !== '') {
            $qb->andWhere('t.title LIKE :query OR t.description LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if ($mode !== null && $mode !== '') {
            $qb->andWhere('t.mode = :mode')
                ->setParameter('mode', $mode);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('t.startDate >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('t.startDate <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        $sortExpr = self::ALLOWED_SORT_FIELDS[$sortBy] ?? self::ALLOWED_SORT_FIELDS['startDate'];
        $direction = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $qb->orderBy($sortExpr, $direction);

        if ($limit !== null) {
            $qb->setMaxResults($limit)->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function countFiltered(
        ?string $query = null,
        ?string $status = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        ?string $mode = null
    ): int {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        if ($query !== null && $query !== '') {
            $qb->andWhere('t.title LIKE :query OR t.description LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if ($mode !== null && $mode !== '') {
            $qb->andWhere('t.mode = :mode')
                ->setParameter('mode', $mode);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('t.startDate >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('t.startDate <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
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

    public function countAllTournaments(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOngoingTournaments(): int
    {
        $now = new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.startDate <= :now')
            ->andWhere('t.endDate IS NULL OR t.endDate >= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPaidTournaments(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.entryFee > 0')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFreeTournaments(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.entryFee <= 0')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
