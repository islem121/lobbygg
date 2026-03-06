<?php

namespace App\Service;

use App\Entity\LeaderboardEntry;
use App\Entity\Tournament;
use App\Entity\TournamentScoreRecord;
use App\Entity\User;
use App\Repository\LeaderboardEntryRepository;
use App\Repository\TournamentScoreRecordRepository;
use Doctrine\ORM\EntityManagerInterface;

class TournamentFinalizationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LeaderboardEntryRepository $leaderboardRepository,
        private readonly TournamentScoreRecordRepository $scoreRepository
    ) {
    }

    /**
     * Generate/refresh final ranking for a finished tournament.
     * If score records exist, they are reused; otherwise plausible random scores are generated.
     *
     * @return array<int, array{rank:int,userId:int,username:string,score:int,points:int,wins:int,losses:int}>
     */
    public function finishTournament(Tournament $tournament): array
    {
        $participations = $tournament->getParticipations()->toArray();
        if ($participations === []) {
            return [];
        }

        if (strtolower((string) $tournament->getStatus()) !== 'finished') {
            $tournament->setStatus('finished');
            $this->entityManager->flush();
        }

        $scored = [];
        $hasRealScores = false;
        foreach ($participations as $participation) {
            $user = $participation->getUser();
            if (!$user instanceof User) {
                continue;
            }

            $scoreRecord = $this->scoreRepository->findOneForTournamentAndUser($tournament, $user);
            if ($scoreRecord !== null) {
                $hasRealScores = true;
            }

            $scored[] = [
                'participation' => $participation,
                'user' => $user,
                'scoreRecord' => $scoreRecord,
                'score' => $scoreRecord?->getScore() ?? 0,
            ];
        }

        if (!$hasRealScores) {
            shuffle($scored);
            foreach ($scored as $i => &$item) {
                /** @var User $user */
                $user = $item['user'];
                $scoreRecord = new TournamentScoreRecord();
                $scoreRecord->setTournament($tournament);
                $scoreRecord->setUser($user);
                $scoreRecord->setScore(max(1, (count($scored) - $i) * 10));
                $scoreRecord->setSource('random_ranking');
                $this->entityManager->persist($scoreRecord);
                $item['scoreRecord'] = $scoreRecord;
                $item['score'] = $scoreRecord->getScore();
            }
            unset($item);
        } else {
            usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        }

        foreach ($this->leaderboardRepository->findBy(['tournament' => $tournament]) as $old) {
            $this->entityManager->remove($old);
        }

        $rows = [];
        $rank = 1;
        $total = max(1, count($scored));
        foreach ($scored as $item) {
            /** @var User $user */
            $user = $item['user'];
            $score = (int) $item['score'];
            $points = match ($rank) {
                1 => 100,
                2 => 70,
                3 => 50,
                default => max(10, 40 - (($rank - 4) * 3)),
            };
            $wins = max(0, $total - $rank);
            $losses = $rank - 1;

            $entry = new LeaderboardEntry();
            $entry->setTournament($tournament);
            $entry->setUser($user);
            $entry->setRank($rank);
            $entry->setPoints($points);
            $entry->setWins($wins);
            $entry->setLosses($losses);
            $this->entityManager->persist($entry);
            $item['participation']->setStatus($rank === 1 ? 'winner' : 'eliminated');

            $rows[] = [
                'rank' => $rank,
                'userId' => (int) $user->getId(),
                'username' => (string) ($user->getUsername() ?? $user->getEmail()),
                'score' => $score,
                'points' => $points,
                'wins' => $wins,
                'losses' => $losses,
            ];
            ++$rank;
        }

        $this->entityManager->flush();

        return $rows;
    }
}
