<?php

namespace App\Controller\Admin;

use App\Service\AnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AnalyticsController extends AbstractController
{
    #[Route('/admin/analytics', name: 'app_admin_analytics', methods: ['GET'])]
    public function index(AnalyticsService $analyticsService): JsonResponse
    {
        return $this->json($analyticsService->buildAnalytics()->toArray());
    }

    #[Route('/admin/analytics/dashboard', name: 'app_admin_analytics_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('admin/analytics/dashboard.html.twig');
    }
}

