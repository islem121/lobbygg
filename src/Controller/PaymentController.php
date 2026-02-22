<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    #[Route('/payment/checkout/{id}', name: 'app_payment_checkout')]
    public function checkout(Order $order, PaymentService $paymentService): Response
    {
        $checkoutUrl = $paymentService->createCheckoutSession($order);
        return $this->redirect($checkoutUrl, 303);
    }

    #[Route('/payment/success/{id}', name: 'app_payment_success')]
    public function success(Order $order, EntityManagerInterface $em): Response
    {
        $order->setStatus('paid');
        $em->flush();

        return $this->render('payment/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/payment/cancel/{id}', name: 'app_payment_cancel')]
    public function cancel(Order $order): Response
    {
        return $this->render('payment/cancel.html.twig', [
            'order' => $order,
        ]);
    }
}
