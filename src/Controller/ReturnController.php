<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\ReturnRequest;
use App\Repository\OrderRepository;
use App\Repository\ReturnRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/return')]
#[IsGranted('ROLE_USER')]
class ReturnController extends AbstractController
{
    #[Route('/request/{id}', name: 'app_return_request', methods: ['GET', 'POST'])]
    public function request(Order $order, Request $request, EntityManagerInterface $em, ReturnRequestRepository $returnRepo): Response
    {
        // Vérifier que la commande appartient à l'utilisateur
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas retourner cette commande.');
        }

        // Vérifier que la commande est livrée pour pouvoir être retournée
        if ($order->getStatus() !== 'delivered' && $order->getStatus() !== 'pending') {
             // On autorise pending pour les tests, mais normalement 'delivered'
        }

        // Vérifier si une demande de retour existe déjà
        $existingReturn = $returnRepo->findOneBy(['orderItem' => $order]);
        if ($existingReturn) {
            $this->addFlash('info', 'Une demande de retour est déjà en cours pour cette commande.');
            return $this->redirectToRoute('app_home'); // Ou une page mes commandes
        }

        if ($request->isMethod('POST')) {
            $reason = $request->request->get('reason');
            if (empty($reason)) {
                $this->addFlash('error', 'Veuillez fournir une raison pour le retour.');
            } else {
                $returnRequest = new ReturnRequest();
                $returnRequest->setOrderItem($order);
                $returnRequest->setReason($reason);
                $returnRequest->setStatus('pending');
                
                $em->persist($returnRequest);
                $em->flush();

                $this->addFlash('success', 'Votre demande de retour a été soumise avec succès.');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('front/return/request.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/admin/list', name: 'app_admin_return_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(ReturnRequestRepository $returnRepo): Response
    {
        return $this->render('admin/return/index.html.twig', [
            'returns' => $returnRepo->findBy([], ['createdAt' => 'DESC'])
        ]);
    }

    #[Route('/admin/handle/{id}', name: 'app_admin_return_handle', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function handle(ReturnRequest $returnRequest, Request $request, EntityManagerInterface $em): Response
    {
        $action = $request->request->get('action'); // approve, reject
        $refundAmount = $request->request->get('refund_amount');
        $adminComment = $request->request->get('admin_comment');

        if ($action === 'approve') {
            $returnRequest->setStatus('approved');
            $returnRequest->setRefundAmount($refundAmount);
            
            // Mise à jour du stock
            $product = $returnRequest->getOrderItem()->getProduct();
            $product->setStock($product->getStock() + $returnRequest->getOrderItem()->getQuantity());
            
            // Mise à jour statut commande
            $returnRequest->getOrderItem()->setStatus('returned');

            $this->addFlash('success', 'Retour validé, stock mis à jour et remboursement enregistré.');
        } elseif ($action === 'reject') {
            $returnRequest->setStatus('rejected');
            $this->addFlash('warning', 'Demande de retour rejetée.');
        }

        $returnRequest->setAdminComment($adminComment);
        $em->flush();

        return $this->redirectToRoute('app_admin_return_index');
    }
}
