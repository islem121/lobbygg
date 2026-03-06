<?php

namespace App\Controller;

use App\Entity\ExternalTournamentInterest;
use App\Repository\ExternalTournamentInterestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ExternalTournamentInterestController extends AbstractController
{
    #[Route('/external-tournaments/interested', name: 'external_tournament_interested', methods: ['POST'])]
    public function interested(
        Request $request,
        EntityManagerInterface $entityManager,
        ExternalTournamentInterestRepository $interestRepository
    ): JsonResponse {
        // External tournaments are discovery-only; this stores "interest" signals.
        $this->denyAccessUnlessGranted('ROLE_USER');

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['success' => false, 'error' => 'Malformed JSON body.'], 400);
        }

        if (!is_array($payload)) {
            return $this->json(['success' => false, 'error' => 'Invalid payload.'], 400);
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $source = trim((string) ($payload['source'] ?? ''));
        $url = trim((string) ($payload['url'] ?? ''));

        if ($title === '' || $source === '' || $url === '') {
            return $this->json(['success' => false, 'error' => 'title, source and url are required.'], 400);
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return $this->json(['success' => false, 'error' => 'Invalid url format.'], 400);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $existing = $interestRepository->findOneByUserAndUrl($user, $url);
        if ($existing !== null) {
            return $this->json(['success' => true, 'message' => 'Already marked as interested.']);
        }

        $interest = new ExternalTournamentInterest();
        $interest->setUser($user);
        $interest->setTitle($title);
        $interest->setSource($source);
        $interest->setUrl($url);
        $entityManager->persist($interest);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'You marked interest in this tournament',
        ]);
    }
}
