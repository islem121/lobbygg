<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\TournamentParticipation;
use App\Form\TournamentType;
use App\Repository\TournamentParticipationRepository;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TournamentController extends AbstractController
{
    #[Route('/tournaments', name: 'front_tournaments', methods: ['GET'])]
    public function index(
        TournamentRepository $tournamentRepository,
        TournamentParticipationRepository $participationRepository
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_tournament_index');
        }

        $rows = $tournamentRepository->findWithParticipantsCount();
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
        ]);
    }

    #[Route('/tournaments/new', name: 'front_tournaments_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $tournament = new Tournament();
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
        ]);
    }

    #[Route('/tournaments/{id}', name: 'front_tournaments_show', methods: ['GET'])]
    public function show(
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository
    ): Response {
        $user = $this->getUser();
        $isJoined = false;

        if ($user) {
            $isJoined = null !== $participationRepository->findOneByUserAndTournament($user, $tournament);
        }

        return $this->render('front/modules/tournament_show.html.twig', [
            'page' => 'tournaments',
            'tournament' => $tournament,
            'participantsCount' => $participationRepository->countByTournament($tournament),
            'isJoined' => $isJoined,
            'participations' => $tournament->getParticipations(),
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
        EntityManagerInterface $entityManager
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
            $this->addFlash('error', 'Tournament is full.');

            return $this->redirectToRoute('front_tournaments_show', ['id' => $tournament->getId()]);
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

    #[Route('/tournaments/{id}/leave', name: 'front_tournaments_leave', methods: ['POST'])]
    public function leave(
        Request $request,
        Tournament $tournament,
        TournamentParticipationRepository $participationRepository,
        EntityManagerInterface $entityManager
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
}
