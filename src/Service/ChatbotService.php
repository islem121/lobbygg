<?php

namespace App\Service;

use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChatbotService
{
    public function __construct(
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function getResponse(string $message, ?User $user): array
    {
        $message = mb_strtolower($message);

        if ($this->containsAny($message, ['bonjour', 'salut', 'hey', 'hello'])) {
            return [
                'type' => 'text',
                'content' => "Bonjour ! Je suis l'assistant intelligent de Lobby.gg. Comment puis-je vous aider aujourd'hui ? Vous pouvez me demander de rechercher un produit, de voir vos commandes ou d'avoir des recommandations."
            ];
        }

        if ($this->containsAny($message, ['recherche', 'trouve', 'cherche', 'produit', 'voir'])) {
            return $this->handleSearch($message);
        }

        if ($this->containsAny($message, ['commande', 'achat', 'statut', 'mes commandes'])) {
            return $this->handleOrderInfo($user);
        }

        if ($this->containsAny($message, ['recommande', 'conseille', 'suggestion', 'top', 'meilleur'])) {
            return $this->handleRecommendation();
        }

        return [
            'type' => 'text',
            'content' => "Désolé, je n'ai pas bien compris votre demande. Essayez de me demander : 'Cherche un produit', 'Mes commandes' ou 'Recommande moi quelque chose'."
        ];
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function handleSearch(string $message): array
    {
        // On essaie d'extraire le terme de recherche
        $words = explode(' ', $message);
        $searchTerms = array_diff($words, ['recherche', 'trouve', 'cherche', 'un', 'des', 'le', 'la', 'les', 'pour', 'produit', 'moi']);
        $term = implode(' ', $searchTerms);

        if (empty(trim($term))) {
            return [
                'type' => 'text',
                'content' => "Que souhaitez-vous rechercher ? (Exemple : 'Cherche souris gamer')"
            ];
        }

        $products = $this->productRepository->createQueryBuilder('p')
            ->where('p.name LIKE :term OR p.description LIKE :term')
            ->setParameter('term', '%' . trim($term) . '%')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        if (empty($products)) {
            return [
                'type' => 'text',
                'content' => "Je n'ai trouvé aucun produit correspondant à '" . trim($term) . "'. Voulez-vous essayer un autre mot-clé ?"
            ];
        }

        $response = "Voici ce que j'ai trouvé pour '" . trim($term) . "' :\n";
        foreach ($products as $product) {
            $response .= "- " . $product->getName() . " (" . $product->getPrice() . " DT)\n";
        }

        return [
            'type' => 'products',
            'content' => $response,
            'items' => array_map(fn($p) => [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'price' => $p->getPrice(),
                'url' => $this->urlGenerator->generate('front_marketplace') // On redirige vers le marketplace pour l'instant
            ], $products)
        ];
    }

    private function handleOrderInfo(?User $user): array
    {
        if (!$user) {
            return [
                'type' => 'text',
                'content' => "Vous devez être connecté pour voir vos commandes."
            ];
        }

        $orders = $this->orderRepository->findBy(['user' => $user], ['orderDate' => 'DESC'], 3);

        if (empty($orders)) {
            return [
                'type' => 'text',
                'content' => "Vous n'avez pas encore passé de commande sur notre marketplace."
            ];
        }

        $response = "Voici vos 3 dernières commandes :\n";
        foreach ($orders as $order) {
            $response .= "- Commande #" . $order->getId() . " : " . $order->getProduct()->getName() . " (Statut: " . $order->getStatus() . ")\n";
        }

        return [
            'type' => 'text',
            'content' => $response
        ];
    }

    private function handleRecommendation(): array
    {
        // Recommandations basées sur les derniers produits ajoutés
        $products = $this->productRepository->findBy([], ['createdAt' => 'DESC'], 3);

        $response = "Je vous recommande ces nouveautés :\n";
        foreach ($products as $product) {
            $response .= "- " . $product->getName() . " (" . $product->getPrice() . " DT)\n";
        }

        return [
            'type' => 'products',
            'content' => $response,
            'items' => array_map(fn($p) => [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'price' => $p->getPrice()
            ], $products)
        ];
    }
}
