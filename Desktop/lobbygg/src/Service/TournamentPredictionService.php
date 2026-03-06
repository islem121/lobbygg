<?php

namespace App\Service;

use App\Dto\TournamentPredictionDto;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\LeaderboardEntryRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentParticipationRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserSkillRatingRepository;
use App\Repository\VoucherRepository;

class TournamentPredictionService
{
    public function __construct(
        private readonly VoucherRepository $voucherRepository,
        private readonly TournamentParticipationRepository $participationRepository,
        private readonly TournamentRepository $tournamentRepository,
        private readonly TournamentMatchRepository $matchRepository,
        private readonly UserSkillRatingRepository $skillRepository,
        private readonly LeaderboardEntryRepository $leaderboardRepository
    ) {
    }

    public function predict(Tournament $tournament): TournamentPredictionDto
    {
        $maxPlayers = max(1, (int) $tournament->getMaxPlayers());
        $sold = $this->safeVoucherCount($tournament);
        $currentFill = ($sold / $maxPlayers) * 100;

        $hoursToStart = $this->hoursToStart($tournament);
        $salesSpeedPerHour = $this->estimateVoucherSalesPerHour($tournament);
        $historicalAverageFill = $this->historicalAverageFillRate();

        $probability = min(100.0, max(0.0, round(
            ($currentFill * 0.5) + ($historicalAverageFill * 0.3) + (min(100, $salesSpeedPerHour * 10) * 0.2),
            2
        )));

        $remainingSlots = max(0, $maxPlayers - $sold);
        $estimatedHoursToFull = $salesSpeedPerHour > 0 ? round($remainingSlots / $salesSpeedPerHour, 2) : null;

        $futureSold = $sold;
        if ($hoursToStart !== null && $salesSpeedPerHour > 0) {
            $futureSold = min($maxPlayers, (int) round($sold + ($salesSpeedPerHour * $hoursToStart)));
        }
        $predictedPrizePool = $tournament->isPaid()
            ? round((float) $tournament->getEntryFee() * $futureSold, 2)
            : 0.0;
        $topPlayers = $this->predictTopPlayers($tournament);

        return new TournamentPredictionDto(
            probabilityFull: $probability,
            estimatedHoursToFull: $estimatedHoursToFull,
            predictedPrizePool: $predictedPrizePool,
            topPlayers: $topPlayers
        );
    }

    private function hoursToStart(Tournament $tournament): ?float
    {
        $startDate = $tournament->getStartDate();
        if ($startDate === null) {
            return null;
        }

        $seconds = $startDate->getTimestamp() - (new \DateTimeImmutable())->getTimestamp();

        return $seconds > 0 ? round($seconds / 3600, 2) : 0.0;
    }

    private function estimateVoucherSalesPerHour(Tournament $tournament): float
    {
        $sold = $this->safeVoucherCount($tournament);
        if ($sold <= 0) {
            return 0.0;
        }

        $createdAt = $tournament->getCreatedAt() ?? new \DateTimeImmutable('-1 day');
        $hoursSinceCreation = max(1.0, ((new \DateTimeImmutable())->getTimestamp() - $createdAt->getTimestamp()) / 3600);

        return round($sold / $hoursSinceCreation, 4);
    }

    private function historicalAverageFillRate(): float
    {
        try {
            $tournaments = $this->tournamentRepository->findAll();
        } catch (\Throwable) {
            return 0.0;
        }
        if ($tournaments === []) {
            return 0.0;
        }

        $sum = 0.0;
        $count = 0;
        foreach ($tournaments as $tournament) {
            $maxPlayers = max(1, (int) $tournament->getMaxPlayers());
            try {
                $participants = $this->participationRepository->countByTournament($tournament);
            } catch (\Throwable) {
                $participants = 0;
            }
            $sum += ($participants / $maxPlayers) * 100;
            $count++;
        }

        return $count > 0 ? round($sum / $count, 2) : 0.0;
    }

    /**
     * @return array<int, array{userId:int,username:string,score:float,winRate:float,rating:int}>
     */
    private function predictTopPlayers(Tournament $tournament): array
    {
        try {
            $globalRows = $this->leaderboardRepository->getGlobalLeaderboardRows(200);
        } catch (\Throwable) {
            $globalRows = [];
        }
        $players = [];
        foreach ($tournament->getParticipations() as $participation) {
            $user = $participation->getUser();
            if (!$user instanceof User) {
                continue;
            }

            try {
                $matches = $this->matchRepository->findByUserAndTournament($tournament, $user);
            } catch (\Throwable) {
                $matches = [];
            }
            $wins = 0;
            foreach ($matches as $match) {
                if ($match->getWinner()?->getId() === $user->getId()) {
                    $wins++;
                }
            }

            $totalMatches = count($matches);
            $winRate = $totalMatches > 0 ? $wins / $totalMatches : 0.5;
            try {
                $rating = $this->skillRepository->findByUser($user)?->getRating() ?? 1000;
            } catch (\Throwable) {
                $rating = 1000;
            }

            $globalRow = null;
            foreach ($globalRows as $row) {
                if ((int) $row['userId'] === (int) $user->getId()) {
                    $globalRow = $row;
                    break;
                }
            }
            $rankScore = $globalRow !== null ? (int) $globalRow['totalPoints'] : 0;

            $score = round(($winRate * 60) + (($rating / 2000) * 25) + (min(15, $rankScore / 100)), 2);

            $players[] = [
                'userId' => (int) $user->getId(),
                'username' => (string) ($user->getUsername() ?? $user->getEmail()),
                'score' => $score,
                'winRate' => round($winRate * 100, 2),
                'rating' => (int) $rating,
            ];
        }

        usort($players, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($players, 0, 3);
    }

    private function safeVoucherCount(Tournament $tournament): int
    {
        try {
            return $this->voucherRepository->countByTournament($tournament);
        } catch (\Throwable) {
            return 0;
        }
    }
}
