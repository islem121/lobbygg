<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère la conversation entre deux utilisateurs triée par date
     */
    public function findConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la liste des derniers contacts avec qui l'utilisateur a échangé
     */
    public function findLastContacts(User $user): array
    {
        $messages = $this->createQueryBuilder('m')
            ->where('m.sender = :user OR m.receiver = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $lastMessages = [];
        $contactsSeen = [];

        foreach ($messages as $message) {
            $contact = $message->getSender() === $user ? $message->getReceiver() : $message->getSender();
            $contactId = $contact->getId();

            if (!isset($contactsSeen[$contactId])) {
                $contactsSeen[$contactId] = true;
                $lastMessages[] = $message;
            }
        }

        return $lastMessages;
    }

    /**
     * Compte les messages non lus pour un utilisateur
     */
    public function countUnreadMessages(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.receiver = :user')
            ->andWhere('m.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
