<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VoiceSearchController extends AbstractController
{
    #[Route('/voice/search', name: 'app_voice_search', methods: ['POST'])]
    public function search(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $transcript = $data['transcript'] ?? '';

        // Simple parsing logic (French)
        // "Je cherche un téléphone moins de 1000 dinars"
        
        $maxPrice = null;
        $keyword = null;

        // Extract price (digits)
        if (preg_match('/(\d+)/', $transcript, $matches)) {
            $maxPrice = (float)$matches[1];
        }

        // Extract keyword (simplified: remove stop words and price related words)
        // This is a naive implementation, but sufficient for the demo
        $cleanText = str_replace(['je', 'cherche', 'un', 'une', 'des', 'le', 'la', 'les', 'moins', 'de', 'dinars', 'dinar', 'euro', 'euros', 'prix', 'coûte', 'pour', 'qui', 'est', 'sont'], ' ', strtolower($transcript));
        $cleanText = preg_replace('/\d+/', '', $cleanText);
        $cleanText = preg_replace('/\s+/', ' ', $cleanText); // Normalize spaces
        $cleanText = trim($cleanText);
        
        if (!empty($cleanText)) {
            $keyword = $cleanText; 
        }

        $products = $productRepository->searchByVoiceCriteria($keyword, $maxPrice);

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'image' => $product->getImage(),
                'url' => '#' // No detail page available
            ];
        }

        // Construct response message
        $message = "J'ai trouvé " . count($results) . " produits";
        if ($keyword) $message .= " pour " . trim($keyword);
        if ($maxPrice) $message .= " à moins de " . $maxPrice . " dinars";
        $message .= ".";

        if (empty($results)) {
            $message = "Désolé, je n'ai trouvé aucun produit correspondant à votre recherche.";
        }

        return new JsonResponse([
            'products' => $results,
            'message' => $message
        ]);
    }
}
