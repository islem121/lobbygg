<?php

namespace App\Controller\Api;

use App\Repository\TournamentRepository;
use App\Service\LeaderboardService;
use App\Service\TournamentPredictionService;
use App\Service\WaitingListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TournamentAdvancedApiController extends AbstractController
{
    #[Route('/tournaments/{id}/leaderboard', name: 'api_tournament_leaderboard', methods: ['GET'])]
    public function tournamentLeaderboard(
        int $id,
        TournamentRepository $tournamentRepository,
        LeaderboardService $leaderboardService
    ): JsonResponse {
        $tournament = $tournamentRepository->find($id);
        if (!$tournament) {
            return $this->json(['success' => false, 'error' => 'Tournament not found.'], 404);
        }

        return $this->json([
            'success' => true,
            'tournamentId' => (int) $tournament->getId(),
            'leaderboard' => $leaderboardService->getTournamentLeaderboard($tournament),
        ]);
    }

    #[Route('/leaderboard/global', name: 'api_global_leaderboard', methods: ['GET'])]
    public function globalLeaderboard(LeaderboardService $leaderboardService): JsonResponse
    {
        return $this->json([
            'success' => true,
            'leaderboard' => $leaderboardService->getGlobalLeaderboard(100),
        ]);
    }

    #[Route('/tournaments/{id}/waiting-list', name: 'api_tournament_waiting_list', methods: ['GET'])]
    public function waitingList(
        int $id,
        TournamentRepository $tournamentRepository,
        WaitingListService $waitingListService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $tournament = $tournamentRepository->find($id);
        if (!$tournament) {
            return $this->json(['success' => false, 'error' => 'Tournament not found.'], 404);
        }

        return $this->json([
            'success' => true,
            'tournamentId' => (int) $tournament->getId(),
            'waitingList' => $waitingListService->getWaitingList($tournament),
        ]);
    }

    #[Route('/tournaments/{id}/prediction', name: 'api_tournament_prediction', methods: ['GET'])]
    public function prediction(
        int $id,
        TournamentRepository $tournamentRepository,
        TournamentPredictionService $predictionService
    ): JsonResponse {
        $tournament = $tournamentRepository->find($id);
        if (!$tournament) {
            return $this->json(['success' => false, 'error' => 'Tournament not found.'], 404);
        }

        return $this->json([
            'success' => true,
            'tournamentId' => (int) $tournament->getId(),
            ...$predictionService->predict($tournament)->toArray(),
        ]);
    }
}

