<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Entity\Notification;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/messages')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_messages_index')]
    public function index(MessageRepository $messageRepository, UserRepository $userRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $lastMessages = $messageRepository->findLastContacts($user);
        
        // Recherche de nouveaux contacts
        $search = $request->query->get('q');
        $searchResults = [];
        if ($search) {
            $searchResults = $userRepository->createQueryBuilder('u')
                ->where('u.id != :id')
                ->andWhere('u.nom LIKE :q OR u.prenom LIKE :q OR u.username LIKE :q')
                ->setParameter('id', $user->getId())
                ->setParameter('q', '%'.$search.'%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }
        
        return $this->render('front/messages/index.html.twig', [
            'lastMessages' => $lastMessages,
            'activeContact' => null,
            'messages' => [],
            'searchResults' => $searchResults,
            'searchQuery' => $search
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_messages_conversation')]
    public function conversation(
        User $contact,
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Marquer les messages comme lus
        $unreadMessages = $messageRepository->findBy([
            'sender' => $contact,
            'receiver' => $user,
            'isRead' => false
        ]);

        foreach ($unreadMessages as $msg) {
            $msg->setIsRead(true);
        }
        $entityManager->flush();

        $lastMessages = $messageRepository->findLastContacts($user);
        $messages = $messageRepository->findConversation($user, $contact);

        // Recherche de nouveaux contacts (même dans la vue conversation)
        $search = $request->query->get('q');
        $searchResults = [];
        if ($search) {
            $searchResults = $userRepository->createQueryBuilder('u')
                ->where('u.id != :id')
                ->andWhere('u.nom LIKE :q OR u.prenom LIKE :q OR u.username LIKE :q')
                ->setParameter('id', $user->getId())
                ->setParameter('q', '%'.$search.'%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }

        return $this->render('front/messages/index.html.twig', [
            'lastMessages' => $lastMessages,
            'activeContact' => $contact,
            'messages' => $messages,
            'searchResults' => $searchResults,
            'searchQuery' => $search
        ]);
    }

    #[Route('/send/{id}', name: 'app_messages_send', methods: ['POST'])]
    public function send(
        User $receiver,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (empty(trim($content))) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $message = new Message();
        $message->setSender($user);
        $message->setReceiver($receiver);
        $message->setContent($content);
        
        $entityManager->persist($message);

        // Ajouter une notification
        $notif = new Notification();
        $notif->setUser($receiver);
        $notif->setActor($user);
        $notif->setType('new_message');
        $notif->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($notif);

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('H:i'),
                'isOwn' => true
            ]
        ]);
    }

    #[Route('/unread-count', name: 'app_messages_unread_count', methods: ['GET'])]
    public function getUnreadCount(MessageRepository $messageRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['count' => 0]);
        }

        return new JsonResponse(['count' => $messageRepository->countUnreadMessages($user)]);
    }
}
