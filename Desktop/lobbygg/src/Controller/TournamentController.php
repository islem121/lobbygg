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
use App\Service\LeaderboardService;
use App\Service\TournamentNotificationService;
use App\Service\TournamentPredictionService;
use App\Service\TournamentVoucherService;
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
        $listData = $this->buildTournamentListData($request, $tournamentRepository, $participationRepository, 6);

        return $this->render('front/modules/tournaments.html.twig', [
            'page' => 'tournaments',
            'tournaments' => $listData['tournaments'],
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

    #[Route('/tournaments/ajax-list', name: 'front_tournaments_ajax_list', methods: ['GET'])]
    public function ajaxList(
        Request $request,
        TournamentRepository $tournamentRepository,
        TournamentParticipationRepository $participationRepository
    ): JsonResponse {
        $listData = $this->buildTournamentListData($request, $tournamentRepository, $participationRepository, 6);

        return $this->json([
            'success' => true,
            'tournaments' => $this->serializeTournamentRows($listData['tournaments']),
            'pagination' => [
                'page' => $listData['pagination']['page'],
                'totalPages' => $listData['pagination']['totalPages'],
            ],
            'html' => $this->renderView('front/modules/_tournaments_results.html.twig', [
                'tournaments' => $listData['tournaments'],
            ]),
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

    #[Route('/tournaments/{id}', name: 'front_tournaments_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository,
        VoucherRepository $voucherRepository,
        TournamentVoucherService $voucherService,
        LeaderboardService $leaderboardService,
        WaitingListService $waitingListService,
        TournamentPredictionService $predictionService
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
        $prediction = null;

        try {
            $leaderboardRows = $leaderboardService->getTournamentLeaderboard($tournament);
            if (strtolower((string) $tournament->getStatus()) === 'finished' && $leaderboardRows === []) {
                // Lazy rebuild for finished tournaments that were completed before leaderboard generation was wired.
                $leaderboardService->generateForTournament($tournament);
                $leaderboardRows = $leaderboardService->getTournamentLeaderboard($tournament);
            }
        } catch (\Throwable) {
            // Backward compatibility while advanced tables are not migrated yet.
        }

        try {
            $waitingListRows = $waitingListService->getWaitingList($tournament);
        } catch (\Throwable) {
            // Backward compatibility while waiting-list tables are not migrated yet.
        }

        try {
            $prediction = $predictionService->predict($tournament)->toArray();
        } catch (\Throwable) {
            // Prediction should degrade gracefully when advanced tables are unavailable.
            $prediction = null;
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
            'prediction' => $prediction,
            'tournamentPublicUrl' => $this->buildPublicTournamentUrl($tournament),
            'facebookShareQuote' => $this->buildFacebookShareQuote($tournament),
            'shareText' => $this->buildTournamentShareText($tournament),
        ]);
    }

    #[Route('/tournaments/{id}/edit', name: 'front_tournaments_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

    #[Route('/tournaments/{id}/delete', name: 'front_tournaments_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
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

    #[Route('/tournaments/{id}/join', name: 'front_tournaments_join', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function join(
        Request $request,
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository,
        VoucherRepository $voucherRepository,
        EntityManagerInterface $entityManager,
        WaitingListService $waitingListService,
        TournamentNotificationService $tournamentNotificationService
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
        $tournamentNotificationService->notifyUser(
            $user,
            $tournament,
            sprintf('You joined tournament "%s".', (string) $tournament->getTitle())
        );

        $this->addFlash('success', 'You joined the tournament.');

        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}/purchase-voucher', name: 'front_tournaments_purchase_voucher', requirements: ['id' => '\d+'], methods: ['POST'])]
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

    #[Route('/tournaments/{id}/prize-pool', name: 'front_tournaments_prize_pool', requirements: ['id' => '\d+'], methods: ['GET'])]
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

    #[Route('/tournaments/{id}/share/feed', name: 'front_tournaments_share_feed', requirements: ['id' => '\d+'], methods: ['POST'])]
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
        $post->setContent($this->buildTournamentShareText($tournament));
        $entityManager->persist($post);
        $entityManager->flush();

        $this->addFlash('success', 'Tournament shared on your feed.');
        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}/share/{platform}', name: 'front_tournaments_share_external', requirements: ['id' => '\d+', 'platform' => '[a-zA-Z]+'], methods: ['GET'])]
    public function shareExternal(Tournament $tournament, string $platform): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $platform = strtolower($platform);
        $absoluteUrl = $this->buildPublicTournamentUrl($tournament);
        // Must stay identical to feed-share text.
        $message = $this->buildTournamentShareText($tournament);

        if ($platform === 'facebook') {
            $facebookAppId = (string) ($_ENV['FACEBOOK_APP_ID'] ?? $_SERVER['FACEBOOK_APP_ID'] ?? '');
            if ($facebookAppId !== '') {
                return $this->redirect(
                    'https://www.facebook.com/dialog/share?app_id='
                    .rawurlencode($facebookAppId)
                    .'&display=popup'
                    .'&href='
                    .rawurlencode($absoluteUrl)
                    .'&quote='
                    .rawurlencode($message)
                    .'&redirect_uri='
                    .rawurlencode($absoluteUrl)
                );
            }

            return $this->redirect(
                'https://www.facebook.com/sharer/sharer.php?u='
                .rawurlencode($absoluteUrl)
                .'&quote='
                .rawurlencode($message)
            );
        }

        if ($platform === 'instagram') {
            $this->addFlash('info', 'Instagram does not allow caption prefill from web links. Use this exact text: '.$message);
            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
        }

        $this->addFlash('error', 'Unsupported sharing platform.');
        return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}/leave', name: 'front_tournaments_leave', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function leave(
        Request $request,
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository,
        EntityManagerInterface $entityManager,
        WaitingListService $waitingListService,
        TournamentNotificationService $tournamentNotificationService
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
        $tournamentNotificationService->notifyUser(
            $user,
            $tournament,
            sprintf('You left tournament "%s".', (string) $tournament->getTitle())
        );

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
        EntityManagerInterface $entityManager,
        LeaderboardService $leaderboardService
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
        try {
            $leaderboardService->generateForTournament($participation->getTournament());
        } catch (\Throwable) {
            // Backward compatibility while advanced tables are not migrated yet.
        }

        $this->addFlash('success', 'Participation status updated.');

        return $this->redirectToRoute('front_tournaments_show', ['id' => $participation->getTournament()?->getId()]);
    }

    #[Route('/tournaments/vouchers', name: 'front_tournament_vouchers', methods: ['GET'])]
    public function vouchers(Request $request, VoucherRepository $voucherRepository, TournamentRepository $tournamentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $q = trim((string) $request->query->get('q', ''));
        $tournamentId = max(0, (int) $request->query->get('tournament', 0));
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $totalItems = $voucherRepository->countByUserFiltered($user, $q !== '' ? $q : null, $tournamentId > 0 ? $tournamentId : null);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);
        $vouchers = $voucherRepository->findByUserFiltered(
            $user,
            $q !== '' ? $q : null,
            $tournamentId > 0 ? $tournamentId : null,
            $perPage,
            ($page - 1) * $perPage
        );

        return $this->render('front/modules/tournament_vouchers.html.twig', [
            'page' => 'tournaments',
            'vouchers' => $vouchers,
            'tournaments' => $tournamentRepository->findBy([], ['title' => 'ASC']),
            'filters' => ['q' => $q, 'tournament' => $tournamentId > 0 ? $tournamentId : null],
            'pagination' => ['page' => $page, 'totalPages' => $totalPages],
        ]);
    }

    #[Route('/tournaments/vouchers/ajax-list', name: 'front_tournament_vouchers_ajax_list', methods: ['GET'])]
    public function vouchersAjaxList(Request $request, VoucherRepository $voucherRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $q = trim((string) $request->query->get('q', ''));
        $tournamentId = max(0, (int) $request->query->get('tournament', 0));
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $totalItems = $voucherRepository->countByUserFiltered($user, $q !== '' ? $q : null, $tournamentId > 0 ? $tournamentId : null);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);
        $vouchers = $voucherRepository->findByUserFiltered(
            $user,
            $q !== '' ? $q : null,
            $tournamentId > 0 ? $tournamentId : null,
            $perPage,
            ($page - 1) * $perPage
        );

        return $this->json([
            'success' => true,
            'vouchers' => $this->serializeVoucherRows($vouchers),
            'pagination' => ['page' => $page, 'totalPages' => $totalPages],
            'html' => $this->renderView('front/modules/_tournament_vouchers_results.html.twig', [
                'vouchers' => $vouchers,
            ]),
        ]);
    }

    /**
     * @return array{
     *   filters: array<string,mixed>,
     *   sort: array{by:string,dir:string},
     *   tournaments: array<int, array{entity:Tournament,participantsCount:int,isJoined:bool}>,
     *   pagination: array{page:int,perPage:int,totalItems:int,totalPages:int}
     * }
     */
    private function buildTournamentListData(
        Request $request,
        TournamentRepository $tournamentRepository,
        TournamentParticipationRepository $participationRepository,
        int $perPage
    ): array {
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

        return [
            'filters' => $filters,
            'sort' => $sort,
            'tournaments' => $tournaments,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ],
        ];
    }

    /**
     * @param array<int, array{entity:Tournament,participantsCount:int,isJoined:bool}> $rows
     * @return array<int, array<string,mixed>>
     */
    private function serializeTournamentRows(array $rows): array
    {
        $payload = [];
        foreach ($rows as $row) {
            $tournament = $row['entity'];
            $payload[] = [
                'id' => (int) $tournament->getId(),
                'title' => (string) $tournament->getTitle(),
                'description' => (string) $tournament->getDescription(),
                'status' => (string) $tournament->getStatus(),
                'mode' => (string) $tournament->getMode(),
                'entryFee' => (float) $tournament->getEntryFee(),
                'isPaid' => $tournament->isPaid(),
                'maxPlayers' => (int) $tournament->getMaxPlayers(),
                'participantsCount' => (int) $row['participantsCount'],
                'isJoined' => (bool) $row['isJoined'],
                'startDate' => $tournament->getStartDate()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $payload;
    }

    /**
     * @param array<int, \App\Entity\Voucher> $vouchers
     * @return array<int, array<string,mixed>>
     */
    private function serializeVoucherRows(array $vouchers): array
    {
        $payload = [];
        foreach ($vouchers as $voucher) {
            $payload[] = [
                'id' => (int) $voucher->getId(),
                'code' => (string) $voucher->getCode(),
                'amount' => (float) $voucher->getAmount(),
                'tournamentTitle' => (string) ($voucher->getTournament()?->getTitle() ?? 'Unknown'),
                'purchasedAt' => $voucher->getPurchasedAt()?->format(\DateTimeInterface::ATOM),
                'isUsed' => $voucher->isUsed(),
            ];
        }

        return $payload;
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

    private function buildTournamentShareText(Tournament $tournament, bool $absoluteUrl = false): string
    {
        $url = $absoluteUrl
            ? $this->buildPublicTournamentUrl($tournament)
            : $this->generateUrl('front_tournaments_show', ['id' => $tournament->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

        return sprintf(
            'I shared this %s tournament: %s. Start %s. %s',
            $tournament->isPaid() ? 'paid' : 'free',
            (string) $tournament->getTitle(),
            $tournament->getStartDate()?->format('Y-m-d H:i') ?? 'N/A',
            $url
        );
    }

    private function buildPublicTournamentUrl(Tournament $tournament): string
    {
        $path = $this->generateUrl('front_tournaments_show', ['id' => $tournament->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        $publicBase = $this->getPublicBaseUrl();
        if ($publicBase !== null) {
            return rtrim($publicBase, '/').$path;
        }

        return $this->generateUrl('front_tournaments_show', ['id' => $tournament->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getPublicBaseUrl(): ?string
    {
        $raw = (string) ($_ENV['PUBLIC_APP_URL'] ?? $_SERVER['PUBLIC_APP_URL'] ?? $_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? '');
        $base = trim($raw);
        if ($base === '') {
            return null;
        }
        if (!str_starts_with($base, 'http://') && !str_starts_with($base, 'https://')) {
            return null;
        }

        return rtrim($base, '/');
    }

    private function buildFacebookShareQuote(Tournament $tournament): string
    {
        return sprintf(
            'Check out this tournament: %s on %s. Join or follow the event!',
            (string) $tournament->getTitle(),
            $tournament->getStartDate()?->format('Y-m-d H:i') ?? 'TBA'
        );
    }
}
