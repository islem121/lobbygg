<?php

namespace App\Controller;

use App\Repository\TournamentNotificationRepository;
use App\Service\AnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(TournamentNotificationRepository $notificationRepository, AnalyticsService $analyticsService): Response
    {
        $stats = $analyticsService->buildAnalytics()->toArray();
        $recentNotifications = $notificationRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentTournamentNotifications' => $recentNotifications,
        ]);
    }

    #[Route('/admin/sponsoring', name: 'app_admin_sponsoring')]
    public function sponsoringManagement(): Response
    {
        return $this->render('admin/sponsoring/index.html.twig');
    }
}
