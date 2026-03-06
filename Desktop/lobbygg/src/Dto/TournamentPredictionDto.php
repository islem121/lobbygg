<?php

namespace App\Dto;

final class TournamentPredictionDto
{
    /**
     * @param array<int, array{userId:int,username:string,score:float,winRate:float,rating:int}> $topPlayers
     */
    public function __construct(
        public readonly float $probabilityFull,
        public readonly ?float $estimatedHoursToFull,
        public readonly float $predictedPrizePool,
        public readonly array $topPlayers
    ) {
    }

    public function toArray(): array
    {
        return [
            'probabilityFull' => $this->probabilityFull,
            'estimatedHoursToFull' => $this->estimatedHoursToFull,
            'predictedPrizePool' => $this->predictedPrizePool,
            'topPlayers' => $this->topPlayers,
        ];
    }
}
