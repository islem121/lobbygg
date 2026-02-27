<?php

namespace App\Dto;

final class TournamentPredictionDto
{
    public function __construct(
        public readonly float $probabilityFull,
        public readonly ?float $estimatedHoursToFull,
        public readonly float $predictedPrizePool
    ) {
    }

    public function toArray(): array
    {
        return [
            'probabilityFull' => $this->probabilityFull,
            'estimatedHoursToFull' => $this->estimatedHoursToFull,
            'predictedPrizePool' => $this->predictedPrizePool,
        ];
    }
}

