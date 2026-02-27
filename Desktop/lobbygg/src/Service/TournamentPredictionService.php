<?php

namespace App\Service;

use App\Dto\TournamentPredictionDto;
use App\Entity\Tournament;
use App\Repository\TournamentParticipationRepository;
use App\Repository\TournamentRepository;
use App\Repository\VoucherRepository;

class TournamentPredictionService
{
    public function __construct(
        private readonly VoucherRepository $voucherRepository,
        private readonly TournamentParticipationRepository $participationRepository,
        private readonly TournamentRepository $tournamentRepository
    ) {
    }

    public function predict(Tournament $tournament): TournamentPredictionDto
    {
        $maxPlayers = max(1, (int) $tournament->getMaxPlayers());
        $sold = $this->voucherRepository->countByTournament($tournament);
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

        return new TournamentPredictionDto(
            probabilityFull: $probability,
            estimatedHoursToFull: $estimatedHoursToFull,
            predictedPrizePool: $predictedPrizePool
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
        $sold = $this->voucherRepository->countByTournament($tournament);
        if ($sold <= 0) {
            return 0.0;
        }

        $createdAt = $tournament->getCreatedAt() ?? new \DateTimeImmutable('-1 day');
        $hoursSinceCreation = max(1.0, ((new \DateTimeImmutable())->getTimestamp() - $createdAt->getTimestamp()) / 3600);

        return round($sold / $hoursSinceCreation, 4);
    }

    private function historicalAverageFillRate(): float
    {
        $tournaments = $this->tournamentRepository->findAll();
        if ($tournaments === []) {
            return 0.0;
        }

        $sum = 0.0;
        $count = 0;
        foreach ($tournaments as $tournament) {
            $maxPlayers = max(1, (int) $tournament->getMaxPlayers());
            $participants = $this->participationRepository->countByTournament($tournament);
            $sum += ($participants / $maxPlayers) * 100;
            $count++;
        }

        return $count > 0 ? round($sum / $count, 2) : 0.0;
    }
}

