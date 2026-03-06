<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\TournamentNotification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TournamentNotificationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function notifyUser(User $user, Tournament $tournament, string $message): void
    {
        $notification = new TournamentNotification();
        $notification->setUser($user);
        $notification->setTournament($tournament);
        $notification->setMessage($message);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function notifyParticipants(Tournament $tournament, string $message): void
    {
        foreach ($tournament->getParticipations() as $participation) {
            $user = $participation->getUser();
            if ($user === null) {
                continue;
            }

            $notification = new TournamentNotification();
            $notification->setUser($user);
            $notification->setTournament($tournament);
            $notification->setMessage($message);
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }
}
