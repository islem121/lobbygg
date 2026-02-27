<?php

namespace App\Service;

use App\Entity\LeaderboardEntry;
use App\Entity\Tournament;
use App\Entity\TournamentParticipation;
use App\Repository\LeaderboardEntryRepository;
use App\Repository\TournamentParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;

class LeaderboardService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LeaderboardEntryRepository $leaderboardRepository,
        private readonly TournamentParticipationRepository $participationRepository
    ) {
    }

    /**
     * Rebuild leaderboard rows for a finished tournament.
     *
     * Ranking strategy:
     * - winner => 3 points
     * - confirmed => 2 points
     * - registered => 1 point
     * - eliminated => 0 point
     */
    public function generateForTournament(Tournament $tournament): void
    {
        if (strtolower((string) $tournament->getStatus()) !== 'finished') {
            return;
        }

        $existing = $this->leaderboardRepository->findBy(['tournament' => $tournament]);
        foreach ($existing as $entry) {
            $this->entityManager->remove($entry);
        }
        $this->entityManager->flush();

        $participations = $tournament->getParticipations()->toArray();
        usort($participations, function (TournamentParticipation $a, TournamentParticipation $b): int {
            return $this->scoreParticipation($b) <=> $this->scoreParticipation($a);
        });

        $rank = 1;
        foreach ($participations as $participation) {
            $entry = new LeaderboardEntry();
            $entry->setTournament($tournament);
            $entry->setUser($participation->getUser());
            $entry->setWins($participation->getStatus() === 'winner' ? 1 : 0);
            $entry->setLosses($participation->getStatus() === 'winner' ? 0 : 1);
            $entry->setPoints($this->scoreParticipation($participation));
            $entry->setRank($rank++);
            $this->entityManager->persist($entry);
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<int, array{
     *   rank:int,
     *   userId:int,
     *   username:string,
     *   wins:int,
     *   losses:int,
     *   points:int
     * }>
     */
    public function getTournamentLeaderboard(Tournament $tournament): array
    {
        $rows = [];
        foreach ($this->leaderboardRepository->findByTournamentOrdered($tournament) as $entry) {
            $rows[] = [
                'rank' => $entry->getRank(),
                'userId' => (int) $entry->getUser()?->getId(),
                'username' => (string) ($entry->getUser()?->getUsername() ?? 'Unknown'),
                'wins' => $entry->getWins(),
                'losses' => $entry->getLosses(),
                'points' => $entry->getPoints(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array{
     *   userId:int,
     *   username:string,
     *   totalWins:int,
     *   tournamentsPlayed:int,
     *   totalPrizeWon:float,
     *   totalPoints:int
     * }>
     */
    public function getGlobalLeaderboard(int $limit = 100): array
    {
        $rows = $this->leaderboardRepository->getGlobalLeaderboardRows($limit);
        $result = [];

        foreach ($rows as $row) {
            $userId = (int) $row['userId'];
            $result[] = [
                'userId' => $userId,
                'username' => (string) $row['username'],
                'totalWins' => (int) $row['totalWins'],
                'tournamentsPlayed' => (int) $row['tournamentsPlayed'],
                'totalPrizeWon' => $this->computeTotalPrizeWonForUser($userId),
                'totalPoints' => (int) $row['totalPoints'],
            ];
        }

        return $result;
    }

    private function computeTotalPrizeWonForUser(int $userId): float
    {
        $winnerEntries = $this->leaderboardRepository->createQueryBuilder('l')
            ->andWhere('l.user = :userId')
            ->andWhere('l.rank = 1')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        $sum = 0.0;
        foreach ($winnerEntries as $entry) {
            $tournament = $entry->getTournament();
            if (!$tournament || !$tournament->isPaid()) {
                continue;
            }
            $participantsCount = $this->participationRepository->countByTournament($tournament);
            $sum += ((float) $tournament->getEntryFee()) * $participantsCount;
        }

        return round($sum, 2);
    }

    private function scoreParticipation(TournamentParticipation $participation): int
    {
        return match ($participation->getStatus()) {
            'winner' => 3,
            'confirmed' => 2,
            'registered' => 1,
            default => 0,
        };
    }
}
