<?php

namespace App\Dto;

final class AnalyticsDto
{
    /**
     * @param array<int, array{tournamentId:int,title:string,revenue:float,soldVouchers:int}> $revenuePerTournament
     * @param array<int, array{month:string,players:int}> $monthlyGrowth
     * @param array<int, array{tournamentId:int,title:string,revenue:float}> $top5HighestRevenue
     * @param array<int, array{username:string,totalPoints:int,totalWins:int}> $topPlayers
     * @param array<int, array{tournamentId:int,title:string,sold:int}> $voucherSales
     */
    public function __construct(
        public readonly float $totalRevenue,
        public readonly int $totalTournaments,
        public readonly int $activeTournaments,
        public readonly int $totalPlayers,
        public readonly float $totalPrizePools,
        public readonly ?array $mostPopularTournament,
        public readonly array $revenuePerTournament,
        public readonly array $monthlyGrowth,
        public readonly float $averageFillRate,
        public readonly float $voucherSellRate,
        public readonly array $top5HighestRevenue,
        public readonly array $topPlayers,
        public readonly array $voucherSales
    ) {
    }

    public function toArray(): array
    {
        return [
            'totalRevenue' => $this->totalRevenue,
            'totalTournaments' => $this->totalTournaments,
            'activeTournaments' => $this->activeTournaments,
            'totalPlayers' => $this->totalPlayers,
            'totalPrizePools' => $this->totalPrizePools,
            'mostPopularTournament' => $this->mostPopularTournament,
            'revenuePerTournament' => $this->revenuePerTournament,
            'monthlyGrowth' => $this->monthlyGrowth,
            'averageFillRate' => $this->averageFillRate,
            'voucherSellRate' => $this->voucherSellRate,
            'top5HighestRevenue' => $this->top5HighestRevenue,
            'topPlayers' => $this->topPlayers,
            'voucherSales' => $this->voucherSales,
        ];
    }
}
