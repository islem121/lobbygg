<?php

namespace App\Service;

use App\Entity\Product;
use OpenAI\Client;
use Psr\Log\LoggerInterface;

class AiComparisonService
{
    private Client $client;

    public function __construct(
        string $openaiApiKey, 
        private LoggerInterface $logger,
        private string $aiModel = 'gpt-4o-mini',
        ?string $aiBaseUri = null
    ) {
        $factory = \OpenAI::factory()->withApiKey($openaiApiKey);
        
        if ($aiBaseUri) {
            $factory = $factory->withBaseUri($aiBaseUri);
        }

        $this->client = $factory->make();
        $this->aiModel = $aiModel;
    }

    /**
     * @param Product[] $products
     */
    public function compareProducts(array $products): string
    {
        // 1. Build the prompt
        $prompt = "Agis comme un expert e-commerce gaming. Compare les produits suivants (format HTML, tableau comparatif, sans balises <html>/<body>, juste le contenu). Sois concis, technique et vendeur.\n\n";
        
        foreach ($products as $product) {
            $prompt .= sprintf(
                "- Produit: %s\n  Prix: %s\n  Description: %s\n\n",
                $product->getName(),
                number_format($product->getPrice(), 2) . ' €',
                strip_tags($product->getDescription()) // Clean description
            );
        }

        $prompt .= "Génère un tableau HTML avec les classes 'table table-bordered table-dark table-striped'.\n";
        $prompt .= "Les colonnes doivent être les produits.\n";
        $prompt .= "Les lignes doivent être des critères pertinents (ex: Performance, Design, Rapport Qualité/Prix, Usage recommandé).\n";
        $prompt .= "Ajoute une ligne finale 'Notre avis' avec une conclusion courte pour chaque produit.\n";
        $prompt .= "Ne mets pas de ```html autour, donne juste le code HTML brut.";

        try {
            $response = $this->client->chat()->create([
                'model' => $this->aiModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es un expert en matériel gaming qui aide les clients à choisir le meilleur produit.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
            ]);

            $html = $response->choices[0]->message->content;
            
            // Clean markdown code blocks if present
            $html = str_replace(['```html', '```'], '', $html);
            
            return trim($html);
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI Error: ' . $e->getMessage());
            
            return '<div class="alert alert-danger mb-3"><i class="fas fa-exclamation-triangle"></i> Erreur lors de la génération du comparatif : ' . $e->getMessage() . '</div>';
        }
    }
}
