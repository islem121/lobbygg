<?php

namespace App\Service;

use App\Entity\Tournament;

class TournamentAiGeneratorService
{
    /**
     * Simulated AI generation for tournament defaults.
     * This keeps generation deterministic enough for admin workflows.
     *
     * @return array{
     *   title:string,
     *   description:string,
     *   mode:string,
     *   startDate:\DateTimeInterface,
     *   endDate:\DateTimeInterface,
     *   maxPlayers:int,
     *   status:string,
     *   entryFee:float
     * }
     */
    public function generate(): array
    {
        $themes = [
            ['name' => 'Neon Night', 'game' => 'Valorant'],
            ['name' => 'Iron Clash', 'game' => 'League of Legends'],
            ['name' => 'Phantom Cup', 'game' => 'Counter-Strike 2'],
            ['name' => 'Skyline Series', 'game' => 'Fortnite'],
            ['name' => 'Apex Hunt', 'game' => 'Apex Legends'],
        ];
        $theme = $themes[array_rand($themes)];
        $mode = Tournament::MODES[array_rand(Tournament::MODES)];
        $start = (new \DateTimeImmutable('+'.random_int(1, 20).' days'))->setTime(random_int(10, 20), 0);
        $end = $start->modify('+'.random_int(1, 3).' days');
        $maxPlayers = match ($mode) {
            Tournament::MODE_SOLO => random_int(32, 128),
            Tournament::MODE_DUO => random_int(16, 64),
            Tournament::MODE_SQUAD => random_int(8, 32),
            default => 32,
        };
        $entryFee = random_int(0, 1) === 1 ? (float) random_int(5, 40) : 0.0;

        return [
            'title' => sprintf('%s %s %s', $theme['game'], $theme['name'], strtoupper($mode)),
            'description' => sprintf(
                'Auto-generated competitive %s event for %s players. Bracket, anti-cheat checks, and live moderation enabled.',
                $mode,
                $theme['game']
            ),
            'mode' => $mode,
            'startDate' => $start,
            'endDate' => $end,
            'maxPlayers' => $maxPlayers,
            'status' => 'upcoming',
            'entryFee' => $entryFee,
        ];
    }
}
