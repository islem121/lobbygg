<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\TournamentMatch;
use App\Entity\TournamentMatchmakingQueueEntry;
use App\Entity\TournamentParticipation;
use App\Entity\TournamentScoreRecord;
use App\Entity\User;
use App\Entity\UserSkillRating;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentMatchmakingQueueEntryRepository;
use App\Repository\TournamentParticipationRepository;
use App\Repository\TournamentScoreRecordRepository;
use App\Repository\UserSkillRatingRepository;
use Doctrine\ORM\EntityManagerInterface;

class TournamentMatchmakingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TournamentMatchmakingQueueEntryRepository $queueRepository,
        private readonly TournamentMatchRepository $matchRepository,
        private readonly TournamentParticipationRepository $participationRepository,
        private readonly TournamentScoreRecordRepository $scoreRepository,
        private readonly UserSkillRatingRepository $skillRepository
    ) {
    }

    /**
     * Queue user and auto-create matches when enough queued users are available.
     *
     * @return array{
     *   success:bool,
     *   message:string,
     *   queueCount:int,
     *   createdMatches:array<int, array{id:int,playerOne:string,playerTwo:string,round:int,status:string}>
     * }
     */
    public function joinQueue(Tournament $tournament, User $user): array
    {
        if (strtolower((string) $tournament->getStatus()) === 'finished') {
            return ['success' => false, 'message' => 'Tournament is finished.', 'queueCount' => 0, 'createdMatches' => []];
        }

        $participation = $this->participationRepository->findOneByUserAndTournament($user, $tournament);
        if (!$participation instanceof TournamentParticipation) {
            return ['success' => false, 'message' => 'Join tournament before entering matchmaking queue.', 'queueCount' => 0, 'createdMatches' => []];
        }

        $existing = $this->queueRepository->findForTournamentAndUser($tournament, $user);
        if (!$existing) {
            $entry = new TournamentMatchmakingQueueEntry();
            $entry->setTournament($tournament);
            $entry->setUser($user);
            $this->entityManager->persist($entry);
            $this->entityManager->flush();
        }

        $created = $this->createMatchesFromQueue($tournament);
        $queueCount = $this->queueRepository->countQueue($tournament);

        return [
            'success' => true,
            'message' => $existing ? 'Already in queue.' : 'Added to matchmaking queue.',
            'queueCount' => $queueCount,
            'createdMatches' => $created,
        ];
    }

    /**
     * Generate a tournament round from confirmed participants.
     *
     * @return array{
     *   success:bool,
     *   message:string,
     *   createdMatches:array<int, array{id:int,playerOne:string,playerTwo:string,round:int,status:string}>
     * }
     */
    public function generateMatchesForTournament(Tournament $tournament): array
    {
        $confirmed = [];
        foreach ($tournament->getParticipations() as $participation) {
            if (!$participation instanceof TournamentParticipation) {
                continue;
            }
            if ($participation->getStatus() !== 'confirmed') {
                continue;
            }
            $user = $participation->getUser();
            if (!$user instanceof User) {
                continue;
            }
            $confirmed[] = $user;
        }

        if (count($confirmed) < 2) {
            return ['success' => false, 'message' => 'Not enough confirmed participants to generate matches.', 'createdMatches' => []];
        }

        $pairs = $this->buildSmartPairs($tournament, $confirmed);
        if ($pairs === []) {
            return ['success' => false, 'message' => 'No valid pairs generated.', 'createdMatches' => []];
        }

        $round = $this->matchRepository->findMaxRoundForTournament($tournament) + 1;
        $created = [];
        foreach ($pairs as [$playerOne, $playerTwo]) {
            $match = new TournamentMatch();
            $match->setTournament($tournament);
            $match->setRoundNumber($round);
            $match->setPlayerOne($playerOne);
            $match->setPlayerTwo($playerTwo);
            $match->setStatus(TournamentMatch::STATUS_PENDING);
            $this->entityManager->persist($match);
            $created[] = $match;
        }
        $this->entityManager->flush();

        $payload = [];
        foreach ($created as $match) {
            $payload[] = [
                'id' => (int) $match->getId(),
                'playerOne' => (string) ($match->getPlayerOne()?->getUsername() ?? 'Unknown'),
                'playerTwo' => (string) ($match->getPlayerTwo()?->getUsername() ?? 'Unknown'),
                'round' => $match->getRoundNumber(),
                'status' => $match->getStatus(),
            ];
        }

        return ['success' => true, 'message' => 'Matches generated successfully.', 'createdMatches' => $payload];
    }

    /**
     * @return array<int, array{
     *   id:int,round:int,status:string,playerOne:string,playerTwo:string,winner:?string,scoreOne:int,scoreTwo:int
     * }>
     */
    public function getMatches(Tournament $tournament): array
    {
        $rows = [];
        foreach ($this->matchRepository->findActiveByTournament($tournament) as $match) {
            $rows[] = [
                'id' => (int) $match->getId(),
                'round' => $match->getRoundNumber(),
                'status' => $match->getStatus(),
                'playerOne' => (string) ($match->getPlayerOne()?->getUsername() ?? 'Unknown'),
                'playerTwo' => (string) ($match->getPlayerTwo()?->getUsername() ?? 'Unknown'),
                'winner' => $match->getWinner()?->getUsername(),
                'scoreOne' => $match->getScoreOne(),
                'scoreTwo' => $match->getScoreTwo(),
            ];
        }

        return $rows;
    }

    /**
     * Submit result, update user skill and tournament score record.
     *
     * @return array{success:bool,message:string,match?:array<string,mixed>}
     */
    public function submitResult(
        Tournament $tournament,
        int $matchId,
        int $scoreOne,
        int $scoreTwo
    ): array {
        /** @var TournamentMatch|null $match */
        $match = $this->matchRepository->find($matchId);
        if (!$match || $match->getTournament()?->getId() !== $tournament->getId()) {
            return ['success' => false, 'message' => 'Match not found for this tournament.'];
        }

        if ($match->getStatus() === TournamentMatch::STATUS_FINISHED) {
            return ['success' => false, 'message' => 'Match already finished.'];
        }

        $scoreOne = max(0, $scoreOne);
        $scoreTwo = max(0, $scoreTwo);
        if ($scoreOne === $scoreTwo) {
            $scoreOne += 1; // enforce winner in simple 1v1 flow.
        }

        $playerOne = $match->getPlayerOne();
        $playerTwo = $match->getPlayerTwo();
        if (!$playerOne || !$playerTwo) {
            return ['success' => false, 'message' => 'Invalid match players.'];
        }

        $winner = $scoreOne > $scoreTwo ? $playerOne : $playerTwo;
        $loser = $winner === $playerOne ? $playerTwo : $playerOne;

        $match->setScoreOne($scoreOne);
        $match->setScoreTwo($scoreTwo);
        $match->setWinner($winner);
        $match->setStatus(TournamentMatch::STATUS_FINISHED);
        $this->applyEloUpdate($winner, $loser);
        $this->upsertTournamentScore($tournament, $playerOne, $scoreOne);
        $this->upsertTournamentScore($tournament, $playerTwo, $scoreTwo);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'Match result submitted.',
            'match' => [
                'id' => (int) $match->getId(),
                'winner' => (string) $winner->getUsername(),
                'scoreOne' => $match->getScoreOne(),
                'scoreTwo' => $match->getScoreTwo(),
            ],
        ];
    }

    /**
     * Pair players by nearest skill rating from queue and create 1v1 matches.
     *
     * @return array<int, array{id:int,playerOne:string,playerTwo:string,round:int,status:string}>
     */
    private function createMatchesFromQueue(Tournament $tournament): array
    {
        $queueEntries = $this->queueRepository->findQueueOrdered($tournament);
        if (count($queueEntries) < 2) {
            return [];
        }

        usort($queueEntries, function (TournamentMatchmakingQueueEntry $a, TournamentMatchmakingQueueEntry $b): int {
            return $this->getSkillRating($b->getUser()) <=> $this->getSkillRating($a->getUser());
        });

        $round = $this->matchRepository->findMaxRoundForTournament($tournament) + 1;
        $created = [];

        for ($i = 0; $i + 1 < count($queueEntries); $i += 2) {
            $first = $queueEntries[$i];
            $second = $queueEntries[$i + 1];

            $match = new TournamentMatch();
            $match->setTournament($tournament);
            $match->setRoundNumber($round);
            $match->setPlayerOne($first->getUser());
            $match->setPlayerTwo($second->getUser());
            $match->setStatus(TournamentMatch::STATUS_PENDING);
            $this->entityManager->persist($match);

            $this->entityManager->remove($first);
            $this->entityManager->remove($second);
            $created[] = [
                'id' => 0,
                'playerOne' => (string) ($match->getPlayerOne()?->getUsername() ?? 'Unknown'),
                'playerTwo' => (string) ($match->getPlayerTwo()?->getUsername() ?? 'Unknown'),
                'round' => $round,
                'status' => $match->getStatus(),
            ];
        }

        $this->entityManager->flush();

        // Fill generated IDs.
        $fresh = $this->matchRepository->findPendingByTournament($tournament);
        foreach ($created as $idx => $row) {
            if (!isset($fresh[$idx])) {
                continue;
            }
            $created[$idx]['id'] = (int) $fresh[$idx]->getId();
        }

        return $created;
    }

    /**
     * @param array<int, User> $users
     * @return array<int, array{0:User,1:User}>
     */
    private function buildSmartPairs(Tournament $tournament, array $users): array
    {
        if (count($users) < 2) {
            return [];
        }

        $pool = array_values($users);
        $stats = [];
        foreach ($pool as $user) {
            $stats[(int) $user->getId()] = $this->getUserStats($tournament, $user);
        }

        usort($pool, function (User $a, User $b) use ($stats): int {
            $sa = $stats[(int) $a->getId()];
            $sb = $stats[(int) $b->getId()];
            $scoreA = ($sa['winRate'] * 1000) + $sa['rating'];
            $scoreB = ($sb['winRate'] * 1000) + $sb['rating'];

            return $scoreB <=> $scoreA;
        });

        $pairs = [];
        while (count($pool) >= 2) {
            /** @var User $first */
            $first = array_shift($pool);
            $bestIndex = -1;
            $bestCost = PHP_FLOAT_MAX;

            foreach ($pool as $idx => $candidate) {
                $firstStats = $stats[(int) $first->getId()];
                $candidateStats = $stats[(int) $candidate->getId()];
                $ratingDelta = abs($firstStats['rating'] - $candidateStats['rating']);
                $winRateDelta = abs($firstStats['winRate'] - $candidateStats['winRate']) * 100;
                $headToHead = $this->matchRepository->countHeadToHead($tournament, $first, $candidate);
                $penalty = $headToHead * 10000;
                $cost = $ratingDelta + $winRateDelta + $penalty;

                if ($cost < $bestCost) {
                    $bestCost = $cost;
                    $bestIndex = $idx;
                }
            }

            if ($bestIndex < 0) {
                shuffle($pool);
                $second = array_shift($pool);
                if ($second instanceof User) {
                    $pairs[] = [$first, $second];
                }
                continue;
            }

            /** @var User $second */
            $second = $pool[$bestIndex];
            array_splice($pool, $bestIndex, 1);
            $pairs[] = [$first, $second];
        }

        return $pairs;
    }

    /**
     * @return array{rating:int,winRate:float}
     */
    private function getUserStats(Tournament $tournament, User $user): array
    {
        $rating = $this->getSkillRating($user);
        $matches = $this->matchRepository->findByUserAndTournament($tournament, $user);
        if ($matches === []) {
            return ['rating' => $rating, 'winRate' => 0.5];
        }

        $wins = 0;
        foreach ($matches as $match) {
            if ($match->getWinner()?->getId() === $user->getId()) {
                $wins++;
            }
        }

        return [
            'rating' => $rating,
            'winRate' => round($wins / max(1, count($matches)), 4),
        ];
    }

    private function upsertTournamentScore(Tournament $tournament, User $user, int $delta): void
    {
        $record = $this->scoreRepository->findOneForTournamentAndUser($tournament, $user);
        if (!$record) {
            $record = new TournamentScoreRecord();
            $record->setTournament($tournament);
            $record->setUser($user);
            $record->setScore(0);
            $record->setSource('match');
            $this->entityManager->persist($record);
        }
        $record->setScore($record->getScore() + $delta);
        $record->setSource('match');
    }

    private function applyEloUpdate(User $winner, User $loser): void
    {
        $winnerRating = $this->getOrCreateSkill($winner);
        $loserRating = $this->getOrCreateSkill($loser);

        $k = 24.0;
        $expectedWinner = 1 / (1 + pow(10, ($loserRating->getRating() - $winnerRating->getRating()) / 400));
        $expectedLoser = 1 / (1 + pow(10, ($winnerRating->getRating() - $loserRating->getRating()) / 400));

        $winnerRating->setRating((int) round($winnerRating->getRating() + $k * (1 - $expectedWinner)));
        $loserRating->setRating((int) round($loserRating->getRating() + $k * (0 - $expectedLoser)));
    }

    private function getSkillRating(?User $user): int
    {
        if (!$user) {
            return 1000;
        }

        return $this->getOrCreateSkill($user)->getRating();
    }

    private function getOrCreateSkill(User $user): UserSkillRating
    {
        $skill = $this->skillRepository->findByUser($user);
        if ($skill) {
            return $skill;
        }

        $skill = new UserSkillRating();
        $skill->setUser($user);
        $skill->setRating(1000 + random_int(-100, 100));
        $this->entityManager->persist($skill);

        return $skill;
    }
}
