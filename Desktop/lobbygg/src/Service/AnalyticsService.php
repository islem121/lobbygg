<?php

namespace App\Service;

use App\Dto\AnalyticsDto;
use App\Repository\LeaderboardEntryRepository;
use App\Repository\TournamentParticipationRepository;
use App\Repository\TournamentRepository;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TournamentRepository $tournamentRepository,
        private readonly TournamentParticipationRepository $participationRepository,
        private readonly VoucherRepository $voucherRepository,
        private readonly LeaderboardEntryRepository $leaderboardRepository
    ) {
    }

    public function buildAnalytics(): AnalyticsDto
    {
        $tournaments = $this->tournamentRepository->findAll();
        $totalTournaments = count($tournaments);

        $totalRevenue = 0.0;
        $totalPrizePools = 0.0;
        $revenuePerTournament = [];
        $mostPopularTournament = null;
        $maxParticipants = -1;
        $totalFillPercentage = 0.0;
        $totalSellRate = 0.0;

        foreach ($tournaments as $tournament) {
            $sold = $this->voucherRepository->countByTournament($tournament);
            $participants = $this->participationRepository->countByTournament($tournament);
            $maxPlayers = max(1, (int) $tournament->getMaxPlayers());
            $revenue = (float) $tournament->getEntryFee() * $sold;
            $totalRevenue += $revenue;
            $totalPrizePools += $revenue;

            $revenuePerTournament[] = [
                'tournamentId' => (int) $tournament->getId(),
                'title' => (string) $tournament->getTitle(),
                'revenue' => round($revenue, 2),
                'soldVouchers' => $sold,
            ];

            $fillRate = ($participants / $maxPlayers) * 100;
            $sellRate = ($sold / $maxPlayers) * 100;
            $totalFillPercentage += $fillRate;
            $totalSellRate += $sellRate;

            if ($participants > $maxParticipants) {
                $maxParticipants = $participants;
                $mostPopularTournament = [
                    'id' => (int) $tournament->getId(),
                    'title' => (string) $tournament->getTitle(),
                    'participants' => $participants,
                ];
            }
        }

        usort($revenuePerTournament, static fn (array $a, array $b): int => $b['revenue'] <=> $a['revenue']);
        $top5 = array_slice(array_map(static fn (array $row): array => [
            'tournamentId' => (int) $row['tournamentId'],
            'title' => (string) $row['title'],
            'revenue' => (float) $row['revenue'],
        ], $revenuePerTournament), 0, 5);

        $averageFillRate = $totalTournaments > 0 ? round($totalFillPercentage / $totalTournaments, 2) : 0.0;
        $voucherSellRate = $totalTournaments > 0 ? round($totalSellRate / $totalTournaments, 2) : 0.0;
        $globalRows = $this->leaderboardRepository->getGlobalLeaderboardRows(5);
        $topPlayers = array_map(static fn (array $row): array => [
            'username' => (string) $row['username'],
            'totalPoints' => (int) $row['totalPoints'],
            'totalWins' => (int) $row['totalWins'],
        ], $globalRows);
        $voucherSales = array_slice(array_map(static fn (array $row): array => [
            'tournamentId' => (int) $row['tournamentId'],
            'title' => (string) $row['tournamentTitle'],
            'sold' => (int) $row['soldVouchers'],
        ], $this->voucherRepository->getSalesSummaryByTournament(null)), 0, 10);

        return new AnalyticsDto(
            totalRevenue: round($totalRevenue, 2),
            totalTournaments: $totalTournaments,
            activeTournaments: $this->tournamentRepository->countOngoingTournaments(),
            totalPlayers: $this->countDistinctPlayers(),
            totalPrizePools: round($totalPrizePools, 2),
            mostPopularTournament: $mostPopularTournament,
            revenuePerTournament: $revenuePerTournament,
            monthlyGrowth: $this->monthlyPlayerGrowth(),
            averageFillRate: $averageFillRate,
            voucherSellRate: $voucherSellRate,
            top5HighestRevenue: $top5,
            topPlayers: $topPlayers,
            voucherSales: $voucherSales
        );
    }

    private function countDistinctPlayers(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT tp.user)')
            ->from('App\Entity\TournamentParticipation', 'tp')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{month:string,players:int}>
     */
    private function monthlyPlayerGrowth(): array
    {
        $conn = $this->entityManager->getConnection();
        $rows = $conn->executeQuery(
            'SELECT DATE_FORMAT(registration_date, "%Y-%m") AS month, COUNT(DISTINCT user_id) AS players
             FROM tournament_participation
             GROUP BY DATE_FORMAT(registration_date, "%Y-%m")
             ORDER BY month ASC'
        )->fetchAllAssociative();

        return array_map(static fn (array $row): array => [
            'month' => (string) $row['month'],
            'players' => (int) $row['players'],
        ], $rows);
    }
}
