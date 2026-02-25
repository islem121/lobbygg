<?php

namespace App\Service;

use App\Entity\Tournament;

class TournamentChatbotService
{
    public function answer(string $question, ?Tournament $tournament = null): string
    {
        $q = mb_strtolower(trim($question));
        if ($q === '') {
            return 'Ask me about tournament dates, mode, entry fee, prize pool, or registration rules.';
        }

        if ($tournament !== null) {
            if (str_contains($q, 'prize') || str_contains($q, 'pool')) {
                $pool = $tournament->getPrizePool();
                if ($pool === null) {
                    return 'This is a free tournament. No prize pool is generated from entry fees.';
                }

                return sprintf('Current prize pool is %.2f (entry fee %.2f x participants).', $pool, $tournament->getEntryFee());
            }

            if (str_contains($q, 'fee') || str_contains($q, 'paid') || str_contains($q, 'free')) {
                return $tournament->isPaid()
                    ? sprintf('This tournament is paid. Entry fee is %.2f and a voucher is required to join.', $tournament->getEntryFee())
                    : 'This tournament is free to join.';
            }

            if (str_contains($q, 'mode')) {
                return sprintf('Tournament mode is %s.', strtoupper((string) $tournament->getMode()));
            }

            if (str_contains($q, 'date') || str_contains($q, 'start') || str_contains($q, 'end')) {
                return sprintf(
                    'Start: %s. End: %s.',
                    $tournament->getStartDate()?->format('Y-m-d H:i') ?? 'N/A',
                    $tournament->getEndDate()?->format('Y-m-d') ?? 'N/A'
                );
            }
        }

        if (str_contains($q, 'join')) {
            return 'To join: open the tournament page and click Join. Paid tournaments require an unused voucher purchased from marketplace.';
        }

        if (str_contains($q, 'voucher')) {
            return 'Vouchers are tournament-specific and single-use. Buy one from marketplace before joining a paid tournament.';
        }

        return 'I can help with tournament mode, dates, fees, vouchers, prize pool, and joining rules.';
    }
}
