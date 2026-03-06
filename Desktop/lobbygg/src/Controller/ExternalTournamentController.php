<?php

namespace App\Controller;

use App\Service\ExternalTournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ExternalTournamentController extends AbstractController
{
    #[Route('/api/external-tournaments', name: 'api_external_tournaments', methods: ['GET'])]
    public function index(ExternalTournamentService $externalTournamentService): JsonResponse
    {
        return $this->json($externalTournamentService->getTournaments());
    }
}

