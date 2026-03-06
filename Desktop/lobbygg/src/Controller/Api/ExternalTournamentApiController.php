<?php

namespace App\Controller\Api;

use App\Service\ExternalTournamentScraperService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ExternalTournamentApiController extends AbstractController
{
    #[Route('/api/external-tournaments-scraped', name: 'api_external_tournaments_scraped', methods: ['GET'])]
    public function index(ExternalTournamentScraperService $scraperService): JsonResponse
    {
        // This endpoint demonstrates external tournament scraping integration.
        // API contract: always return "events", even when scraping fails.
        $events = [];
        try {
            $events = $scraperService->fetchEvents();
        } catch (\Throwable) {
            $events = [];
        }

        return $this->json([
            'events' => $events,
        ]);
    }
}
