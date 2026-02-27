<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private ?string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        // On cherche d'abord la clé spécifique, puis la clé générique
        $this->apiKey = $_ENV['GEMINI_API_KEY_SPONSORING'] ?? $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY_SPONSORING'] ?? getenv('GEMINI_API_KEY_SPONSORING') ?? null;
        $this->httpClient = $httpClient;
    }

    public function analyzeDossier(string $offerName, string $motivation, string $message): array
    {
        // Récupération et nettoyage de la clé
        $key = $_ENV['GEMINI_API_KEY_SPONSORING'] ?? $_SERVER['GEMINI_API_KEY_SPONSORING'] ?? getenv('GEMINI_API_KEY_SPONSORING');
        $key = $key ? trim($key) : null;

        if (!$key) {
            return ['score' => 0, 'avis' => "Erreur : La clé API n'est pas détectée. Redémarrez votre serveur."];
        }

        $prompt = "Tu es un expert en sponsoring. Analyse cette demande pour '$offerName'. Motivation: '$motivation'. Message: '$message'. Réponds uniquement en JSON: {\"score\": 85, \"avis\": \"...\"}";

        $urls = [
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $key,
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $key,
            "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=" . $key
        ];

        $lastStatus = 0;

        foreach ($urls as $url) {
            try {
                $response = $this->httpClient->request('POST', $url, [
                    'json' => [
                        'contents' => [['parts' => [['text' => $prompt]]]]
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout' => 15
                ]);

                $lastStatus = $response->getStatusCode();

                if ($lastStatus === 200) {
                    $data = $response->toArray();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    
                    if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                        $result = json_decode($matches[0], true);
                        if ($result && isset($result['score'])) return $result;
                    }
                    return ['score' => 50, 'avis' => "Analyse ok, mais format de réponse inconnu."];
                }
            } catch (\Exception $e) {
                // On continue
            }
        }

        return [
            'score' => 0, 
            'avis' => "Erreur Gemini ($lastStatus). Vérifiez que votre clé est bien celle de 'Gemini API Key sponsoring' et qu'elle est active."
        ];
    }

    public function analyzeContract(array $contractData): array
    {
        $key = $_ENV['GEMINI_API_KEY_SPONSORING'] ?? $_SERVER['GEMINI_API_KEY_SPONSORING'] ?? getenv('GEMINI_API_KEY_SPONSORING');
        $key = $key ? trim($key) : null;

        if (!$key) {
            return ['score' => 0, 'avis' => "Erreur : La clé API n'est pas détectée."];
        }

        $prompt = "Tu es un expert juridique et en sponsoring. Analyse la conformité de ce contrat de sponsoring.
        Données du contrat:
        - Sponsor: {$contractData['sponsor_name']}
        - Client: {$contractData['client_name']}
        - Objet: {$contractData['content']}
        - Signature Sponsor présente: " . ($contractData['has_sponsor_signature'] ? 'OUI' : 'NON') . "
        - Signature Client présente: " . ($contractData['has_client_signature'] ? 'OUI' : 'NON') . "
        
        Critères:
        1. Les champs sont-ils remplis sérieusement (pas de texte vide ou incohérent) ?
        2. La signature du sponsor est-elle présente (indispensable pour que le client signe) ?
        3. Le contenu semble-t-il équilibré ?
        
        Réponds uniquement en JSON: {\"score\": 0-100, \"avis\": \"...\", \"statut\": \"Conforme/Incomplet/Risqué\"}";

        $urls = [
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $key,
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $key
        ];

        $lastStatus = 0;
        foreach ($urls as $url) {
            try {
                $response = $this->httpClient->request('POST', $url, [
                    'json' => ['contents' => [['parts' => [['text' => $prompt]]]]],
                    'headers' => ['Content-Type' => 'application/json'],
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout' => 15
                ]);

                $lastStatus = $response->getStatusCode();
                if ($lastStatus === 200) {
                    $data = $response->toArray();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                        $result = json_decode($matches[0], true);
                        if ($result) return $result;
                    }
                }
            } catch (\Exception $e) {}
        }

        return ['score' => 0, 'avis' => "L'IA n'a pas pu analyser le contrat (Status $lastStatus).", 'statut' => 'Erreur'];
    }
}
