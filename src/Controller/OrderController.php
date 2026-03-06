<?php

namespace App\Controller;

use App\Entity\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/order/{id}/pdf', name: 'app_order_pdf')]
    public function generatePdf(Order $order): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire de la commande
        if ($order->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit d\'accéder à cette commande.');
        }

        // Configurer Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true); // Pour charger les images (logo, produits)

        $dompdf = new Dompdf($pdfOptions);

        // Générer le HTML
        $html = $this->renderView('order/pdf.html.twig', [
            'order' => $order,
            'user' => $this->getUser(),
            'total' => $order->getProduct()->getPrice() * $order->getQuantity()
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Renvoyer le PDF au navigateur
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="commande-' . $order->getId() . '.pdf"',
        ]);
    }
}
