<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalTournamentService
{
    private const PUBLIC_API_URL = 'https://raw.githubusercontent.com/openai/gpt-oss/main/samples/tournaments.json';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * @return array<int, array{
     *   title:string,
     *   game:string,
     *   date:string,
     *   prizePool:string,
     *   platform:string,
     *   url:string
     * }>
     */
    public function getTournaments(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::PUBLIC_API_URL, [
                'timeout' => 6,
                'headers' => ['Accept' => 'application/json'],
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $payload = $response->toArray(false);
                if (is_array($payload)) {
                    $normalized = $this->normalizeEvents($payload);
                    if ($normalized !== []) {
                        return array_slice($normalized, 0, 6);
                    }
                }
            }
        } catch (\Throwable) {
            // Network/API failures fall back to static tournaments.
        }

        return $this->fallbackTournaments();
    }

    /**
     * @param array<mixed> $payload
     * @return array<int, array{title:string,game:string,date:string,prizePool:string,platform:string,url:string}>
     */
    private function normalizeEvents(array $payload): array
    {
        $events = $payload['events'] ?? $payload;
        if (!is_array($events)) {
            return [];
        }

        $result = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $title = trim((string) ($event['title'] ?? ''));
            $url = trim((string) ($event['url'] ?? ''));
            if ($title === '' || $url === '') {
                continue;
            }

            $result[] = [
                'title' => $title,
                'game' => trim((string) ($event['game'] ?? 'Esports')),
                'date' => trim((string) ($event['date'] ?? $event['startDate'] ?? 'TBA')),
                'prizePool' => trim((string) ($event['prizePool'] ?? 'N/A')),
                'platform' => trim((string) ($event['platform'] ?? $event['source'] ?? 'External')),
                'url' => $url,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{title:string,game:string,date:string,prizePool:string,platform:string,url:string}>
     */
    private function fallbackTournaments(): array
    {
        return [
            [
                'title' => 'ESL Challenger League Open',
                'game' => 'Counter-Strike 2',
                'date' => '2026-04-12',
                'prizePool' => '$25,000',
                'platform' => 'Liquipedia',
                'url' => 'https://liquipedia.net/counterstrike',
            ],
            [
                'title' => 'Battlefy Valorant Weekly Cup',
                'game' => 'Valorant',
                'date' => '2026-04-18',
                'prizePool' => '$10,000',
                'platform' => 'Battlefy',
                'url' => 'https://battlefy.com',
            ],
            [
                'title' => 'Challengermode Spring Clash',
                'game' => 'Rocket League',
                'date' => '2026-04-25',
                'prizePool' => '$7,500',
                'platform' => 'Challengermode',
                'url' => 'https://www.challengermode.com',
            ],
        ];
    }
}

