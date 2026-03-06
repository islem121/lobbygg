<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\TournamentParticipation;
use App\Entity\User;
use App\Entity\WaitingListEntry;
use App\Repository\TournamentParticipationRepository;
use App\Repository\WaitingListEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

class WaitingListService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WaitingListEntryRepository $waitingListRepository,
        private readonly TournamentParticipationRepository $participationRepository,
        private readonly TournamentNotificationService $notificationService
    ) {
    }

    /**
     * @return array{added:bool,message:string,position?:int}
     */
    public function addUser(Tournament $tournament, User $user): array
    {
        if ($this->participationRepository->findOneByUserAndTournament($user, $tournament)) {
            return ['added' => false, 'message' => 'You are already an active participant.'];
        }

        $existing = $this->waitingListRepository->findOneByUserAndTournament($user, $tournament);
        if ($existing !== null) {
            return [
                'added' => false,
                'message' => 'You are already in waiting list.',
                'position' => $existing->getPosition(),
            ];
        }

        $position = $this->waitingListRepository->countByTournament($tournament) + 1;
        $entry = new WaitingListEntry();
        $entry->setTournament($tournament);
        $entry->setUser($user);
        $entry->setPosition($position);
        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return ['added' => true, 'message' => 'Added to waiting list.', 'position' => $position];
    }

    /**
     * Promote first waiting user if a slot is available.
     *
     * @return User|null promoted user
     */
    public function promoteNextUser(Tournament $tournament): ?User
    {
        $current = $this->participationRepository->countByTournament($tournament);
        if ($current >= (int) $tournament->getMaxPlayers()) {
            return null;
        }

        $next = $this->waitingListRepository->findNextForPromotion($tournament);
        if (!$next) {
            return null;
        }

        $participation = new TournamentParticipation();
        $participation->setTournament($tournament);
        $participation->setUser($next->getUser());
        $participation->setStatus('registered');
        $this->entityManager->persist($participation);

        $this->entityManager->remove($next);
        $this->entityManager->flush();
        $this->notificationService->notifyUser(
            $participation->getUser(),
            $tournament,
            'You have been promoted from waiting list to active player.'
        );
        $this->reindexPositions($tournament);

        return $participation->getUser();
    }

    /**
     * @return array<int, array{position:int,userId:int,username:string,createdAt:string}>
     */
    public function getWaitingList(Tournament $tournament): array
    {
        $rows = [];
        foreach ($this->waitingListRepository->findByTournamentOrdered($tournament) as $entry) {
            $rows[] = [
                'position' => $entry->getPosition(),
                'userId' => (int) $entry->getUser()?->getId(),
                'username' => (string) ($entry->getUser()?->getUsername() ?? 'Unknown'),
                'createdAt' => (string) $entry->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $rows;
    }

    public function reindexPositions(Tournament $tournament): void
    {
        $entries = $this->waitingListRepository->findByTournamentOrdered($tournament);
        $position = 1;
        foreach ($entries as $entry) {
            $entry->setPosition($position++);
        }
        $this->entityManager->flush();
    }
}
