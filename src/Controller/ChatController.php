<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\IntelligentChatService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/', name: 'app_chat_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Get recent conversations
        // We'll fetch all messages involving the user, ordered by date DESC
        $allMessages = $messageRepository->findRecentConversations($user);
        
        $contacts = [];
        foreach ($allMessages as $msg) {
            $otherUser = ($msg->getSender() === $user) ? $msg->getReceiver() : $msg->getSender();
            $otherId = $otherUser->getId();
            
            // Only keep the latest message per contact
            if (!isset($contacts[$otherId])) {
                $contacts[$otherId] = [
                    'user' => $otherUser,
                    'lastMessage' => $msg,
                ];
            }
        }

        // 2. Get all other users to start new chats (excluding current user)
        // This is a bit heavy for production but fine for demo.
        $allUsers = $userRepository->findAll();
        $potentialContacts = [];
        foreach ($allUsers as $u) {
            if ($u !== $user && !isset($contacts[$u->getId()])) {
                $potentialContacts[] = $u;
            }
        }

        return $this->render('chat/index.html.twig', [
            'contacts' => $contacts,
            'potentialContacts' => $potentialContacts,
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_chat_conversation', methods: ['GET'])]
    public function conversation(User $receiver, MessageRepository $messageRepository, IntelligentChatService $chatService): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        if ($currentUser === $receiver) {
            return $this->redirectToRoute('app_chat_index');
        }

        $messages = $messageRepository->findConversation($currentUser, $receiver);
        $messageMeta = [];
        foreach ($messages as $msg) {
            $messageMeta[$msg->getId()] = $chatService->buildMessageMeta($msg, $currentUser);
        }

        return $this->render('chat/conversation.html.twig', [
            'receiver' => $receiver,
            'messages' => $messages,
            'messageMeta' => $messageMeta,
        ]);
    }

    #[Route('/send/{id}', name: 'app_chat_send', methods: ['POST'])]
    public function send(Request $request, User $receiver, EntityManagerInterface $entityManager, IntelligentChatService $chatService, LoggerInterface $logger): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;

        if (empty($content)) {
            return $this->json(['error' => 'Message cannot be empty'], Response::HTTP_BAD_REQUEST);
        }

        $analysis = $chatService->processOutgoingMessage($content, $currentUser, $receiver);

        $targetLanguage = $analysis['targetLanguage'] ?? 'fr';
        $translatedMessage = $analysis['content'] ?? $content;

        $logger->info('Intelligent chat translation debug', [
            'sender_id' => $currentUser->getId(),
            'sender_origin' => $currentUser->getOrigin(),
            'receiver_id' => $receiver->getId(),
            'receiver_origin' => $receiver->getOrigin(),
            'target_language' => $targetLanguage,
            'translated_message' => $translatedMessage,
        ]);

        if ((bool) $this->getParameter('kernel.debug')) {
            dump($receiver->getOrigin());
            dump($targetLanguage);
            dump($translatedMessage);
        }

        $message = new Message();
        $message->setSender($currentUser);
        $message->setReceiver($receiver);
        $message->setOriginalContent($analysis['originalContent']);
        $message->setContent($analysis['content']);
        $message->setCreatedAt(new \DateTimeImmutable());
        $message->setIsRead(false);
        $message->setIsToxic($analysis['isToxic']);

        $entityManager->persist($message);
        $entityManager->flush();

        $meta = $chatService->buildMessageMeta($message, $currentUser);

        return $this->json([
            'status' => 'success',
            'message' => [
                'id' => $message->getId(),
                'content' => $meta['displayContent'],
                'originalContent' => $meta['originalContent'],
                'translatedContent' => $meta['translatedContent'],
                'createdAt' => $message->getCreatedAt()->format('H:i'),
                'isSelf' => true,
                'isToxic' => $message->isToxic(),
                'senderName' => $currentUser->getUsername(),
                'aiAssisted' => $meta['aiAssisted'],
                'isTranslated' => $meta['isTranslated'],
                'translationLabel' => $meta['sourceLabel'] . ' -> ' . $meta['targetLabel'],
                'showModerationNotice' => $meta['showModerationNotice'],
                'playerNotice' => $meta['playerNotice'],
            ]
        ]);
    }

    #[Route('/poll/{id}', name: 'app_chat_poll', methods: ['GET'])]
    public function poll(User $receiver, MessageRepository $messageRepository, Request $request, IntelligentChatService $chatService): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $lastId = $request->query->get('lastId', 0);
        
        // Fetch conversation again (could be optimized to fetch only > lastId)
        $messages = $messageRepository->findConversation($currentUser, $receiver);
        $newMessages = [];

        foreach ($messages as $msg) {
            if ($msg->getId() > $lastId) {
                $meta = $chatService->buildMessageMeta($msg, $currentUser);
                $newMessages[] = [
                    'id' => $msg->getId(),
                    'content' => $meta['displayContent'],
                    'originalContent' => $meta['originalContent'],
                    'translatedContent' => $meta['translatedContent'],
                    'createdAt' => $msg->getCreatedAt()->format('H:i'),
                    'isSelf' => $msg->getSender() === $currentUser,
                    'senderName' => $msg->getSender()->getUsername(),
                    'isToxic' => $msg->isToxic(),
                    'aiAssisted' => $meta['aiAssisted'],
                    'isTranslated' => $meta['isTranslated'],
                    'translationLabel' => $meta['sourceLabel'] . ' -> ' . $meta['targetLabel'],
                    'showModerationNotice' => $meta['showModerationNotice'],
                    'playerNotice' => $meta['playerNotice'],
                ];
            }
        }

        return $this->json(['messages' => $newMessages]);
    }
}
