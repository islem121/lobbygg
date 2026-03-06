<?php

namespace App\Controller\Admin;

use App\Repository\TournamentRepository;
use App\Repository\VoucherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/vouchers')]
#[IsGranted('ROLE_ADMIN')]
class VoucherController extends AbstractController
{
    #[Route('/', name: 'app_admin_voucher_index', methods: ['GET'])]
    public function index(
        Request $request,
        VoucherRepository $voucherRepository,
        TournamentRepository $tournamentRepository
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $tournamentId = $request->query->getInt('tournament', 0);
        $filterTournamentId = $tournamentId > 0 ? $tournamentId : null;
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        $totalItems = $voucherRepository->countAdminFiltered($q !== '' ? $q : null, $filterTournamentId);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        $summaryRows = $voucherRepository->getSalesSummaryByTournament($filterTournamentId);
        $vouchers = $voucherRepository->findAdminFiltered(
            $q !== '' ? $q : null,
            $filterTournamentId,
            $perPage,
            ($page - 1) * $perPage
        );

        return $this->render('admin/voucher/index.html.twig', [
            'vouchers' => $vouchers,
            'summaryRows' => $summaryRows,
            'tournaments' => $tournamentRepository->findBy([], ['title' => 'ASC']),
            'selectedTournamentId' => $filterTournamentId,
            'q' => $q,
            'pagination' => ['page' => $page, 'totalPages' => $totalPages],
        ]);
    }

    #[Route('/ajax-list', name: 'app_admin_voucher_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, VoucherRepository $voucherRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $tournamentId = $request->query->getInt('tournament', 0);
        $filterTournamentId = $tournamentId > 0 ? $tournamentId : null;
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        $totalItems = $voucherRepository->countAdminFiltered($q !== '' ? $q : null, $filterTournamentId);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        $summaryRows = $voucherRepository->getSalesSummaryByTournament($filterTournamentId);
        $vouchers = $voucherRepository->findAdminFiltered(
            $q !== '' ? $q : null,
            $filterTournamentId,
            $perPage,
            ($page - 1) * $perPage
        );

        return $this->json([
            'success' => true,
            'pagination' => ['page' => $page, 'totalPages' => $totalPages],
            'summaryHtml' => $this->renderView('admin/voucher/_summary_rows.html.twig', ['summaryRows' => $summaryRows]),
            'html' => $this->renderView('admin/voucher/_voucher_rows.html.twig', ['vouchers' => $vouchers]),
        ]);
    }
}
