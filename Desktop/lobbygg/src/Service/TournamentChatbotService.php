<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Repository\TournamentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TournamentChatbotService
{
    /** @var array<int, string> */
    private array $tournamentKeywords = [
        'tournament', 'tournoi', 'join', 'register', 'participants', 'participant',
        'entry fee', 'fee', 'frais', 'prix', 'voucher', 'status', 'upcoming', 'ongoing',
        'finished', 'mode', 'solo', 'duo', 'squad', 'inscription', 'rejoindre',
        'cagnotte', 'prize', 'pool', 'payant', 'gratuit',
    ];

    public function __construct(
        private readonly TournamentRepository $tournamentRepository,
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{success: bool, answer?: string, error?: string}
     */
    public function ask(string $question, ?int $tournamentId = null): array
    {
        $question = trim($question);
        if ($question === '') {
            return ['success' => false, 'error' => 'Question cannot be empty.'];
        }

        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Rate limit exceeded. Please wait a moment.'];
        }

        $q = mb_strtolower($question);
        if (!$this->isTournamentRelated($q)) {
            return ['success' => false, 'answer' => 'I only answer questions related to tournaments.'];
        }

        $tournament = $tournamentId ? $this->tournamentRepository->find($tournamentId) : null;

        $answer = $this->generateAnswer($q, $tournament);
        $this->storeHistory($question);
        $this->logger->info('Tournament chatbot interaction', [
            'question' => $question,
            'answer' => $answer,
            'tournament_id' => $tournamentId,
            'ip' => $this->requestStack->getCurrentRequest()?->getClientIp(),
        ]);

        return ['success' => true, 'answer' => $answer];
    }

    private function isTournamentRelated(string $q): bool
    {
        foreach ($this->tournamentKeywords as $keyword) {
            if (str_contains($q, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function generateAnswer(string $q, ?Tournament $tournament): string
    {
        if ($this->containsAny($q, ['how many', 'combien', 'nombre']) && $this->containsAny($q, ['tournament', 'tournoi'])) {
            return sprintf('There are currently %d tournaments in the platform.', $this->tournamentRepository->countAllTournaments());
        }

        if ($this->containsAny($q, ['ongoing', 'live', 'en cours'])) {
            return sprintf('There are currently %d ongoing tournaments.', $this->tournamentRepository->countOngoingTournaments());
        }

        if ($this->containsAny($q, ['free', 'paid', 'gratuit', 'payant', 'difference'])) {
            return sprintf(
                'Free tournaments have entry fee = 0. Paid tournaments require vouchers before joining. Current split: %d free / %d paid.',
                $this->tournamentRepository->countFreeTournaments(),
                $this->tournamentRepository->countPaidTournaments()
            );
        }

        if ($this->containsAny($q, ['prize', 'pool', 'cagnotte', 'formula', 'calcul'])) {
            if ($tournament !== null && $tournament->isPaid()) {
                return sprintf(
                    'PrizePool = entryFee x numberOfParticipants. For this tournament: %.2f x %d = %.2f.',
                    $tournament->getEntryFee(),
                    $tournament->getParticipations()->count(),
                    (float) $tournament->getPrizePool()
                );
            }

            return 'PrizePool is calculated dynamically with: entryFee x numberOfParticipants. Free tournaments have no prize pool.';
        }

        if ($this->containsAny($q, ['voucher', 'why'])) {
            return 'Vouchers are required for paid tournaments to validate that entry fees were purchased before registration.';
        }

        if ($this->containsAny($q, ['join', 'register', 'inscription', 'rejoindre'])) {
            return 'To join a tournament, open its detail page and click Join. For paid tournaments, an unused voucher is required.';
        }

        if ($tournament !== null && $this->containsAny($q, ['status', 'mode', 'date', 'start', 'end'])) {
            return sprintf(
                'Tournament info: status=%s, mode=%s, start=%s, end=%s, entryFee=%.2f.',
                (string) $tournament->getStatus(),
                (string) $tournament->getMode(),
                $tournament->getStartDate()?->format('Y-m-d H:i') ?? 'N/A',
                $tournament->getEndDate()?->format('Y-m-d') ?? 'N/A',
                $tournament->getEntryFee()
            );
        }

        return 'I can answer tournament mode, dates, fees, vouchers, prize pool, and joining rules.';
    }

    private function checkRateLimit(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $identity = (string) ($request?->getClientIp() ?? 'anonymous');
        $key = 'tournament_chatbot_rate_'.$identity;
        $now = time();

        $timestamps = $this->cache->get($key, function (ItemInterface $item): array {
            $item->expiresAfter(60);
            return [];
        });

        $timestamps = array_values(array_filter($timestamps, static fn (int $ts): bool => $ts > (time() - 60)));
        if (count($timestamps) >= 10) {
            return false;
        }

        $timestamps[] = $now;
        $this->cache->delete($key);
        $this->cache->get($key, function (ItemInterface $item) use ($timestamps): array {
            $item->expiresAfter(60);
            return $timestamps;
        });

        return true;
    }

    private function storeHistory(string $question): void
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return;
        }

        $history = $session->get('tournament_chat_history', []);
        $history[] = $question;
        $history = array_slice($history, -3);
        $session->set('tournament_chat_history', $history);
    }

    /**
     * @param array<int, string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
