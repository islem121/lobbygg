<?php

namespace App\Controller\Api;

use App\Repository\TournamentRepository;
use App\Repository\VoucherRepository;
use App\Service\TournamentChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TournamentApiController extends AbstractController
{
    #[Route('/api/chatbot/tournament', name: 'api_tournament_chatbot', methods: ['POST'])]
    public function chatbot(
        Request $request,
        TournamentRepository $tournamentRepository,
        TournamentChatbotService $chatbotService
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $question = trim((string) ($payload['question'] ?? ''));
        $tournamentId = isset($payload['tournamentId']) ? (int) $payload['tournamentId'] : null;
        $tournament = $tournamentId ? $tournamentRepository->find($tournamentId) : null;

        return $this->json([
            'answer' => $chatbotService->answer($question, $tournament),
        ]);
    }

    #[Route('/api/tournaments/{id}/voucher/check', name: 'api_tournament_voucher_check', methods: ['GET'])]
    public function checkVoucher(
        int $id,
        TournamentRepository $tournamentRepository,
        VoucherRepository $voucherRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $tournament = $tournamentRepository->find($id);
        if (!$tournament) {
            return $this->json(['error' => 'Tournament not found'], 404);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $voucher = $voucherRepository->findValidForUserAndTournament($user, $tournament);

        return $this->json([
            'tournamentId' => $tournament->getId(),
            'isPaid' => $tournament->isPaid(),
            'entryFee' => $tournament->getEntryFee(),
            'hasVoucher' => $voucher !== null,
            'voucherCode' => $voucher?->getCode(),
        ]);
    }
}
