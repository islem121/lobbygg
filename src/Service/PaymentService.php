<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Entity\Order;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentService
{
    private string $stripeSecretKey;
    private bool $simulationMode;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        string $stripeSecretKey,
        bool $stripeSimulationMode = false
    ) {
        $this->simulationMode = $stripeSimulationMode;
        $this->stripeSecretKey = $stripeSecretKey;

        if (!$this->simulationMode) {
            if (str_contains($stripeSecretKey, 'placeholder')) {
                throw new \Exception('Veuillez configurer votre clé secrète Stripe réelle dans le fichier .env (STRIPE_SECRET_KEY) ou activez le mode simulation (STRIPE_SIMULATION_MODE=true).');
            }
            Stripe::setApiKey($this->stripeSecretKey);
        }
    }

    /**
     * @param Order[]|Order $orders
     */
    public function createCheckoutSession(array|Order $orders): string
    {
        if ($orders instanceof Order) {
            $orders = [$orders];
        }

        $firstOrderId = $orders[0]->getId();
        $successUrl = $this->urlGenerator->generate('app_payment_success', [
            'id' => $firstOrderId
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        if ($this->simulationMode) {
            // En mode simulation, on retourne directement l'URL de succès
            return $successUrl;
        }

        $lineItems = [];
        foreach ($orders as $order) {
            $product = $order->getProduct();
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product->getName(),
                        'description' => $product->getDescription(),
                    ],
                    'unit_amount' => $product->getPrice() * 100, // Stripe uses cents
                ],
                'quantity' => $order->getQuantity(),
            ];
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $this->urlGenerator->generate('app_payment_cancel', [
                'id' => $firstOrderId
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'client_reference_id' => (string)$firstOrderId,
        ]);

        return $session->url;
    }
}
