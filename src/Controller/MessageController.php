<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_messages')]
    public function index(ConversationRepository $conversationRepository): Response
    {
        $user = $this->getUser();
        /** @var User $user */
        $conversations = $conversationRepository->findByUser($user);

        return $this->render('front/messages/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/show/{id}', name: 'app_messages_show')]
    public function show(Conversation $conversation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var User $user */
        
        // Vérifier que l'utilisateur fait partie de la conversation
        if ($conversation->getBuyer() !== $user && $conversation->getSeller() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette conversation.');
        }

        // Marquer les messages comme lus
        foreach ($conversation->getMessages() as $message) {
            if ($message->getSender() !== $user && !$message->isRead()) {
                $message->setIsRead(true);
            }
        }
        $em->flush();

        return $this->render('front/messages/show.html.twig', [
            'conversation' => $conversation,
        ]);
    }

    #[Route('/start/{id}', name: 'app_messages_start')]
    public function start(Product $product, ConversationRepository $conversationRepository, EntityManagerInterface $em): Response
    {
        $buyer = $this->getUser();
        /** @var User $buyer */
        $seller = $product->getSeller();

        if (!$seller) {
            $this->addFlash('error', 'Ce produit n\'a pas de vendeur associé.');
            return $this->redirectToRoute('app_marketplace');
        }

        if ($buyer === $seller) {
            $this->addFlash('error', 'Vous ne pouvez pas discuter avec vous-même.');
            return $this->redirectToRoute('app_marketplace_show', ['id' => $product->getId()]);
        }

        // Chercher une conversation existante pour ce produit
        $conversation = $conversationRepository->findExisting($buyer, $seller, $product);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setBuyer($buyer);
            $conversation->setSeller($seller);
            $conversation->setProduct($product);
            $em->persist($conversation);
            $em->flush();
        }

        return $this->redirectToRoute('app_messages_show', ['id' => $conversation->getId()]);
    }

    #[Route('/send/{id}', name: 'app_messages_send', methods: ['POST'])]
    public function send(Request $request, Conversation $conversation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var User $user */
        
        if ($conversation->getBuyer() !== $user && $conversation->getSeller() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $content = $request->request->get('content');
        $audioFile = $request->files->get('audio');

        if (!empty($content) || $audioFile) {
            $message = new Message();
            $message->setConversation($conversation);
            $message->setSender($user);
            
            if ($audioFile) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/audio';
                if (!file_exists($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                
                $fileName = uniqid() . '.webm';
                $audioFile->move($uploadsDirectory, $fileName);
                $message->setAudioPath('uploads/audio/' . $fileName);
                $message->setContent('[Message Vocal]');
            } else {
                $message->setContent($content);
            }
            
            $em->persist($message);
            $em->flush();
        }

        return $this->redirectToRoute('app_messages_show', ['id' => $conversation->getId()]);
    }
}
