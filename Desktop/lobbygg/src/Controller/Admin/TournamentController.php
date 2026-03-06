<?php

namespace App\Controller\Admin;

use App\Entity\Tournament;
use App\Form\TournamentType;
use App\Repository\TournamentRepository;
use App\Service\TournamentAiGeneratorService;
use App\Service\LeaderboardService;
use App\Service\TournamentFinalizationService;
use App\Service\TournamentMatchmakingService;
use App\Service\TournamentNotificationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tournament')]
#[IsGranted('ROLE_ADMIN')]
class TournamentController extends AbstractController
{
    #[Route('/', name: 'app_admin_tournament_index', methods: ['GET'])]
    #[Route('/ajax', name: 'app_admin_tournament_index_legacy', methods: ['GET'])]
    public function index(Request $request, TournamentRepository $tournamentRepository): Response
    {
        $listData = $this->buildListData($request, $tournamentRepository, 10);

        return $this->render('admin/tournament/index.html.twig', [
            'rows' => $listData['rows'],
            'filters' => [
                'q' => $listData['filters']['q'],
                'status' => $listData['filters']['status'],
                'mode' => $listData['filters']['mode'],
                'dateFrom' => $listData['filters']['dateFromRaw'],
                'dateTo' => $listData['filters']['dateToRaw'],
            ],
            'sort' => $listData['sort'],
            'pagination' => $listData['pagination'],
        ]);
    }

    #[Route('/ajax-list', name: 'app_admin_tournament_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, TournamentRepository $tournamentRepository): JsonResponse
    {
        $listData = $this->buildListData($request, $tournamentRepository, 10);

        return $this->json([
            'success' => true,
            'pagination' => [
                'page' => $listData['pagination']['page'],
                'totalPages' => $listData['pagination']['totalPages'],
            ],
            'html' => $this->renderView('admin/tournament/_table_rows.html.twig', [
                'rows' => $listData['rows'],
            ]),
        ]);
    }

    #[Route('/export/pdf', name: 'app_admin_tournament_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, TournamentRepository $tournamentRepository): Response
    {
        [$filters, $sort] = $this->extractListParams($request);
        $rows = $tournamentRepository->findWithParticipantsCountFiltered(
            $filters['q'],
            $filters['status'],
            $filters['dateFrom'],
            $filters['dateTo'],
            $filters['mode'],
            $sort['by'],
            $sort['dir']
        );

        $html = $this->renderView('tournament/list_pdf.html.twig', [
            'title' => 'Backoffice Tournaments',
            'rows' => $rows,
            'filters' => [
                'q' => $filters['q'],
                'status' => $filters['status'],
                'mode' => $filters['mode'],
                'dateFrom' => $filters['dateFromRaw'],
                'dateTo' => $filters['dateToRaw'],
            ],
            'sort' => $sort,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="admin-tournaments.pdf"',
            ]
        );
    }

    #[Route('/new', name: 'app_admin_tournament_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TournamentAiGeneratorService $aiGeneratorService,
        LeaderboardService $leaderboardService
    ): Response
    {
        $tournament = new Tournament();
        if ($request->isMethod('GET') && (string) $request->query->get('ai') === '1') {
            // AI-assisted tournament draft generation.
            $generated = $aiGeneratorService->generate();
            $tournament->setTitle($generated['title']);
            $tournament->setDescription($generated['description']);
            $tournament->setMode($generated['mode']);
            $tournament->setStartDate($generated['startDate']);
            $tournament->setEndDate($generated['endDate']);
            $tournament->setMaxPlayers($generated['maxPlayers']);
            $tournament->setStatus($generated['status']);
            $tournament->setEntryFee($generated['entryFee']);
            $tournament->setIsAiGenerated(true);
        }

        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tournament);
            $entityManager->flush();
            try {
                $leaderboardService->generateForTournament($tournament);
            } catch (\Throwable) {
                // Ignore until advanced leaderboard tables are migrated.
            }

            $this->addFlash('success', 'Tournament created.');

            return $this->redirectToRoute('app_admin_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tournament/new.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
            'isAiDraft' => $tournament->isAiGenerated(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_tournament_show', methods: ['GET'])]
    public function show(Tournament $tournament): Response
    {
        return $this->render('admin/tournament/show.html.twig', [
            'tournament' => $tournament,
        ]);
    }

    #[Route('/{id}/generate-matches', name: 'app_admin_tournament_generate_matches', methods: ['POST'])]
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

    #[Route('/{id}/finalize', name: 'app_admin_tournament_finalize', methods: ['POST'])]
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

    #[Route('/{id}/edit', name: 'app_admin_tournament_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Tournament $tournament,
        EntityManagerInterface $entityManager,
        LeaderboardService $leaderboardService
    ): Response
    {
        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            try {
                $leaderboardService->generateForTournament($tournament);
            } catch (\Throwable) {
                // Ignore until advanced leaderboard tables are migrated.
            }

            $this->addFlash('success', 'Tournament updated.');

            return $this->redirectToRoute('app_admin_tournament_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/tournament/edit.html.twig', [
            'tournament' => $tournament,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_tournament_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tournament->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tournament);
            $entityManager->flush();
            $this->addFlash('success', 'Tournament deleted.');
        }

        return $this->redirectToRoute('app_admin_tournament_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @return array{
     *     0: array{
     *         q: ?string,
     *         status: ?string,
     *         mode: ?string,
     *         dateFrom: ?\DateTimeInterface,
     *         dateTo: ?\DateTimeInterface,
     *         dateFromRaw: ?string,
     *         dateToRaw: ?string
     *     },
     *     1: array{by: string, dir: string}
     * }
     */
    private function extractListParams(Request $request): array
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = trim((string) $request->query->get('status', ''));
        $mode = trim((string) $request->query->get('mode', ''));
        $dateFromRaw = trim((string) $request->query->get('date_from', ''));
        $dateToRaw = trim((string) $request->query->get('date_to', ''));
        $sortBy = (string) $request->query->get('sort_by', 'startDate');
        $sortDir = strtolower((string) $request->query->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $dateFrom = $this->parseDate($dateFromRaw, false);
        $dateTo = $this->parseDate($dateToRaw, true);

        $allowedSortBy = ['title', 'status', 'startDate', 'endDate', 'maxPlayers', 'participantsCount'];
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'startDate';
        }

        return [[
            'q' => $q !== '' ? $q : null,
            'status' => $status !== '' ? $status : null,
            'mode' => $mode !== '' ? $mode : null,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dateFromRaw' => $dateFromRaw !== '' ? $dateFromRaw : null,
            'dateToRaw' => $dateToRaw !== '' ? $dateToRaw : null,
        ], [
            'by' => $sortBy,
            'dir' => $sortDir,
        ]];
    }

    private function parseDate(string $value, bool $endOfDay): ?\DateTimeInterface
    {
        if ($value === '') {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($value);
            if ($endOfDay) {
                return $date->setTime(23, 59, 59);
            }

            return $date->setTime(0, 0, 0);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *   rows: array<int, array{0:Tournament, participantsCount:string}>,
     *   filters: array<string,mixed>,
     *   sort: array{by:string,dir:string},
     *   pagination: array{page:int,perPage:int,totalItems:int,totalPages:int}
     * }
     */
    private function buildListData(Request $request, TournamentRepository $tournamentRepository, int $perPage): array
    {
        [$filters, $sort] = $this->extractListParams($request);
        $page = max(1, (int) $request->query->get('page', 1));
        $totalItems = $tournamentRepository->countFiltered(
            $filters['q'],
            $filters['status'],
            $filters['dateFrom'],
            $filters['dateTo'],
            $filters['mode']
        );
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        $rows = $tournamentRepository->findWithParticipantsCountFiltered(
            $filters['q'],
            $filters['status'],
            $filters['dateFrom'],
            $filters['dateTo'],
            $filters['mode'],
            $sort['by'],
            $sort['dir'],
            $perPage,
            ($page - 1) * $perPage
        );

        return [
            'rows' => $rows,
            'filters' => $filters,
            'sort' => $sort,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],
        ];
    }
}
