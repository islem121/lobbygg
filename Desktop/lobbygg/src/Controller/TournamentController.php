<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Tournament;
use App\Entity\TournamentParticipation;
use App\Form\TournamentType;
use App\Repository\TournamentParticipationRepository;
use App\Repository\TournamentRepository;
use App\Repository\VoucherRepository;
use App\Service\TournamentAiGeneratorService;
use App\Service\TournamentVoucherService;
use App\Service\LeaderboardService;
use App\Service\WaitingListService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TournamentController extends AbstractController
{
    #[Route('/tournaments', name: 'front_tournaments', methods: ['GET'])]
    public function index(
        Request $request,
        TournamentRepository $tournamentRepository,
        TournamentParticipationRepository $participationRepository
    ): Response {
        [$filters, $sort] = $this->extractListParams($request);
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 6;
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
        $tournaments = [];
        $user = $this->getUser();

        foreach ($rows as $row) {
            /** @var Tournament $tournament */
            $tournament = $row[0];
            $participantsCount = (int) $row['participantsCount'];
            $isJoined = false;

            if ($user) {
                $isJoined = null !== $participationRepository->findOneByUserAndTournament($user, $tournament);
            }

            $tournaments[] = [
                'entity' => $tournament,
                'participantsCount' => $participantsCount,
                'isJoined' => $isJoined,
            ];
        }

        return $this->render('front/modules/tournaments.html.twig', [
            'page' => 'tournaments',
            'tournaments' => $tournaments,
            'filters' => [
                'q' => $filters['q'],
                'status' => $filters['status'],
                'mode' => $filters['mode'],
                'dateFrom' => $filters['dateFromRaw'],
                'dateTo' => $filters['dateToRaw'],
            ],
            'sort' => $sort,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    #[Route('/tournaments/export/pdf', name: 'front_tournaments_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, TournamentRepository $tournamentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

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
            'title' => 'Frontoffice Tournaments',
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
                'Content-Disposition' => 'attachment; filename="front-tournaments.pdf"',
            ]
        );
    }

    #[Route('/tournaments/new', name: 'front_tournaments_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TournamentAiGeneratorService $aiGeneratorService
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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

            $this->addFlash('success', 'Tournament created.');

            return $this->redirectToRoute('front_tournaments');
        }

        return $this->render('front/modules/tournament_form.html.twig', [
            'page' => 'tournaments',
            'form' => $form->createView(),
            'tournament' => $tournament,
            'mode' => 'create',
            'isAiDraft' => $tournament->isAiGenerated(),
        ]);
    }

    #[Route('/tournaments/{id}', name: 'front_tournaments_show', methods: ['GET'])]
    public function show(
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository,
        VoucherRepository $voucherRepository,
        TournamentVoucherService $voucherService,
        LeaderboardService $leaderboardService,
        WaitingListService $waitingListService
    ): Response {
        $user = $this->getUser();
        $isJoined = false;
        $hasVoucher = false;

        if ($user) {
            $isJoined = null !== $participationRepository->findOneByUserAndTournament($user, $tournament);
            $hasVoucher = null !== $voucherRepository->findAnyForUserAndTournament($user, $tournament);
        }

        $prizePoolSnapshot = $voucherService->getPrizePoolSnapshot($tournament);

        $leaderboardRows = [];
        $waitingListRows = [];
        try {
            $leaderboardRows = $leaderboardService->getTournamentLeaderboard($tournament);
            $waitingListRows = $waitingListService->getWaitingList($tournament);
        } catch (\Throwable) {
            // Backward compatibility while advanced tables are not migrated yet.
        }

        return $this->render('front/modules/tournament_show.html.twig', [
            'page' => 'tournaments',
            'tournament' => $tournament,
            'participantsCount' => $participationRepository->countByTournament($tournament),
            'isJoined' => $isJoined,
            'hasVoucher' => $hasVoucher,
            'prizePoolSnapshot' => $prizePoolSnapshot,
            'participations' => $tournament->getParticipations(),
            'leaderboardRows' => $leaderboardRows,
            'waitingListRows' => $waitingListRows,
        ]);
    }

    #[Route('/tournaments/{id}/edit', name: 'front_tournaments_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(TournamentType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Tournament updated.');

            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        return $this->render('front/modules/tournament_form.html.twig', [
            'page' => 'tournaments',
            'form' => $form->createView(),
            'tournament' => $tournament,
            'mode' => 'edit',
        ]);
    }

    #[Route('/tournaments/{id}/delete', name: 'front_tournaments_delete', methods: ['POST'])]
    public function delete(Request $request, Tournament $tournament, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_tournament_'.$tournament->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('front_tournaments');
        }

        $entityManager->remove($tournament);
        $entityManager->flush();

        $this->addFlash('success', 'Tournament deleted.');

        return $this->redirectToRoute('front_tournaments');
    }

    #[Route('/tournaments/{id}/join', name: 'front_tournaments_join', methods: ['POST'])]
    public function join(
        Request $request,
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository,
        VoucherRepository $voucherRepository,
        EntityManagerInterface $entityManager,
        WaitingListService $waitingListService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('join_tournament_'.$tournament->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('front_tournaments');
        }

        $user = $this->getUser();
        $existing = $participationRepository->findOneByUserAndTournament($user, $tournament);
        if ($existing) {
            $this->addFlash('error', 'You already joined this tournament.');

            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        $currentCount = $participationRepository->countByTournament($tournament);
        if ($currentCount >= (int) $tournament->getMaxPlayers()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            try {
                $waitingResult = $waitingListService->addUser($tournament, $user);
                $this->addFlash($waitingResult['added'] ? 'info' : 'error', $waitingResult['message']);
            } catch (\Throwable) {
                $this->addFlash('error', 'Tournament is full.');
            }

            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        if ($tournament->isPaid()) {
            $voucher = $voucherRepository->findAnyForUserAndTournament($user, $tournament);
            if ($voucher === null) {
                $this->addFlash(
                    'error',
                    sprintf(
                        'This is a paid tournament (%.2f). Please buy a voucher from marketplace before joining.',
                        $tournament->getEntryFee()
                    )
                );

                return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
            }
        }

        $participation = new TournamentParticipation();
        $participation->setUser($user);
        $participation->setTournament($tournament);
        $participation->setStatus('registered');

        $entityManager->persist($participation);
        $entityManager->flush();

        $this->addFlash('success', 'You joined the tournament.');

        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}/purchase-voucher', name: 'front_tournaments_purchase_voucher', methods: ['POST'])]
    public function purchaseVoucher(
        Request $request,
        Tournament $tournament,
        TournamentVoucherService $voucherService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('purchase_voucher_'.$tournament->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $result = $voucherService->purchaseVoucher($user, $tournament);
        $this->addFlash($result['success'] ? 'success' : 'error', $result['message']);

        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}/prize-pool', name: 'front_tournaments_prize_pool', methods: ['GET'])]
    public function prizePool(Tournament $tournament, TournamentVoucherService $voucherService): JsonResponse
    {
        $snapshot = $voucherService->getPrizePoolSnapshot($tournament);

        return $this->json([
            'entryFee' => $snapshot['entryFee'],
            'soldVouchers' => $snapshot['soldVouchers'],
            'maxPlayers' => $snapshot['maxPlayers'],
            'prizePool' => $snapshot['prizePool'] ?? 0.0,
            'progressPercentage' => $snapshot['progressPercentage'],
            'remainingVouchers' => $snapshot['remainingVouchers'],
            'isPaid' => $snapshot['isPaid'],
            'isSoldOut' => $snapshot['isSoldOut'],
        ]);
    }

    #[Route('/tournaments/{id}/share/feed', name: 'front_tournaments_share_feed', methods: ['POST'])]
    public function shareToFeed(
        Request $request,
        Tournament $tournament,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if (!$this->isCsrfTokenValid('share_tournament_feed_'.$tournament->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $post = new Post();
        $post->setUser($user);
        $post->setType('tournament_share');
        $post->setTitle('Tournament shared: '.$tournament->getTitle());
        $post->setContent(sprintf(
            'I shared this %s tournament: %s. Start %s. %s',
            $tournament->isPaid() ? 'paid' : 'free',
            $tournament->getTitle(),
            $tournament->getStartDate()?->format('Y-m-d H:i') ?? 'N/A',
            $this->generateUrl('front_tournaments_show', ['id' => $tournament->getId()])
        ));
        $entityManager->persist($post);
        $entityManager->flush();

        $this->addFlash('success', 'Tournament shared on your feed.');
        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}/share/{platform}', name: 'front_tournaments_share_external', methods: ['GET'])]
    public function shareExternal(Tournament $tournament, string $platform): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $platform = strtolower($platform);
        $absoluteUrl = $this->generateUrl('front_tournaments_show', ['id' => $tournament->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $message = sprintf(
            '%s tournament: %s (%s, fee %.2f)',
            strtoupper($platform),
            $tournament->getTitle(),
            strtoupper((string) $tournament->getMode()),
            $tournament->getEntryFee()
        );

        if ($platform === 'facebook') {
            return $this->redirect('https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($absoluteUrl));
        }

        if ($platform === 'instagram') {
            $this->addFlash('success', 'Instagram sharing simulated: '.$message.' | URL: '.$absoluteUrl);
            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        $this->addFlash('error', 'Unsupported sharing platform.');
        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}/leave', name: 'front_tournaments_leave', methods: ['POST'])]
    public function leave(
        Request $request,
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository,
        EntityManagerInterface $entityManager,
        WaitingListService $waitingListService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('leave_tournament_'.$tournament->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('front_tournaments');
        }

        $user = $this->getUser();
        $participation = $participationRepository->findOneByUserAndTournament($user, $tournament);
        if (!$participation) {
            $this->addFlash('error', 'You are not registered in this tournament.');

            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        $entityManager->remove($participation);
        $entityManager->flush();

        try {
            $promoted = $waitingListService->promoteNextUser($tournament);
            if ($promoted !== null) {
                $this->addFlash('info', sprintf('Waiting list promotion: %s is now an active participant.', $promoted->getUsername()));
            }
        } catch (\Throwable) {
            // Backward compatibility while waiting-list tables are not migrated yet.
        }

        $this->addFlash('success', 'You left the tournament.');

        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/participation/{id}/status/{status}', name: 'front_tournaments_participation_status', methods: ['POST'])]
    public function updateParticipationStatus(
        Request $request,
        TournamentParticipation $participation,
        string $status,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('participation_status_'.$participation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('front_tournaments_show', ['id' => $participation->getTournament()?->getId()]);
        }

        $allowed = ['registered', 'confirmed', 'eliminated', 'winner'];
        if (!in_array($status, $allowed, true)) {
            $this->addFlash('error', 'Invalid participation status.');

            return $this->redirectToRoute('front_tournaments_show', ['id' => $participation->getTournament()?->getId()]);
        }

        $participation->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', 'Participation status updated.');

        return $this->redirectToRoute('front_tournaments_show', ['id' => $participation->getTournament()?->getId()]);
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
}
