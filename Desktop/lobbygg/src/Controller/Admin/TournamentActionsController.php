<?php

namespace App\Controller\Admin;

use App\Entity\Tournament;
use App\Service\TournamentFinalizationService;
use App\Service\TournamentMatchmakingService;
use App\Service\TournamentNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TournamentActionsController extends AbstractController
{
    #[Route('/admin/tournaments/{id}/generate-matches', name: 'app_admin_tournaments_generate_matches', methods: ['POST'])]
    public function generateMatches(
        Request $request,
        Tournament $tournament,
        TournamentMatchmakingService $matchmakingService
    ): Response {
        if (!$this->isCsrfTokenValid('generate_matches_'.$tournament->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
        }

        $result = $matchmakingService->generateMatchesForTournament($tournament);
        $this->addFlash(
            $result['success'] ? 'success' : 'error',
            sprintf('%s (%d matches).', $result['message'], count($result['createdMatches'] ?? []))
        );

        return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
    }

    #[Route('/admin/tournaments/{id}/finalize', name: 'app_admin_tournaments_finalize', methods: ['POST'])]
    public function finalize(
        Request $request,
        Tournament $tournament,
        TournamentFinalizationService $finalizationService,
        TournamentNotificationService $notificationService
    ): Response {
        if (!$this->isCsrfTokenValid('finalize_tournament_'.$tournament->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
        }

        $rows = $finalizationService->finishTournament($tournament);
        $winner = $rows[0]['username'] ?? null;
        $notificationService->notifyParticipants(
            $tournament,
            $winner !== null
                ? sprintf('Tournament "%s" is finished. Winner: %s.', (string) $tournament->getTitle(), (string) $winner)
                : sprintf('Tournament "%s" is finished.', (string) $tournament->getTitle())
        );

        $this->addFlash('success', sprintf('Tournament finalized. %d leaderboard entries generated.', count($rows)));

        return $this->redirectToRoute('app_admin_tournament_show', ['id' => $tournament->getId()]);
    }
}
