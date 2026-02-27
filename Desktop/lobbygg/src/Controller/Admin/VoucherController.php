<?php

namespace App\Controller\Admin;

use App\Repository\TournamentRepository;
use App\Repository\VoucherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $tournamentId = $request->query->getInt('tournament', 0);
        $filterTournamentId = $tournamentId > 0 ? $tournamentId : null;

        $summaryRows = $voucherRepository->getSalesSummaryByTournament($filterTournamentId);
        $vouchers = $voucherRepository->findBy(
            $filterTournamentId ? ['tournament' => $filterTournamentId] : [],
            ['id' => 'DESC']
        );

        return $this->render('admin/voucher/index.html.twig', [
            'vouchers' => $vouchers,
            'summaryRows' => $summaryRows,
            'tournaments' => $tournamentRepository->findBy([], ['title' => 'ASC']),
            'selectedTournamentId' => $filterTournamentId,
        ]);
    }
}

