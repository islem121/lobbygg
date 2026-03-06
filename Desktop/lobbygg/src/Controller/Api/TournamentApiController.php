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
        TournamentChatbotService $chatbotService
    ): JsonResponse {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['success' => false, 'error' => 'Malformed JSON body.'], 400);
        }

        if (!is_array($payload)) {
            return $this->json(['success' => false, 'error' => 'Invalid payload.'], 400);
        }

        $question = trim((string) ($payload['question'] ?? ''));
        $tournamentId = isset($payload['tournamentId']) ? (int) $payload['tournamentId'] : null;

        try {
            $result = $chatbotService->ask($question, $tournamentId);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => 'Unexpected chatbot error.'], 500);
        }

        $statusCode = 200;
        if (($result['success'] ?? false) === false && isset($result['error'])) {
            $statusCode = 400;
        }

        return $this->json($result, $statusCode);
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
        $voucher = $voucherRepository->findAnyForUserAndTournament($user, $tournament);

        return $this->json([
            'tournamentId' => $tournament->getId(),
            'isPaid' => $tournament->isPaid(),
            'entryFee' => $tournament->getEntryFee(),
            'hasVoucher' => $voucher !== null,
            'voucherCode' => $voucher?->getCode(),
        ]);
    }
}
