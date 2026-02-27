<?php

namespace App\Command;

use App\Entity\Tournament;
use App\Entity\TournamentParticipation;
use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed:tournaments', description: 'Generate tournaments, vouchers, and participations test data')]
class SeedTournamentDataCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var User[] $users */
        $users = $this->entityManager->getRepository(User::class)->findAll();
        if (count($users) < 2) {
            $io->error('Need at least 2 users in database before seeding.');
            return Command::FAILURE;
        }

        $tournaments = [];
        $titles = [
            'City Clash', 'Neon Open', 'Midnight Arena', 'Apex Sprint', 'Iron Bracket',
            'Duel Masters', 'Skyline Cup', 'Pulse League', 'Rapid Royale', 'Weekend Warzone',
        ];

        for ($i = 0; $i < 10; $i++) {
            $t = new Tournament();
            $mode = Tournament::MODES[array_rand(Tournament::MODES)];
            $start = $this->randomStartDate();
            $end = (clone $start)->modify('+'.random_int(1, 3).' day');

            $t->setTitle($titles[$i].' #'.random_int(10, 99));
            $t->setDescription('Seeded tournament for QA and integration testing.');
            $t->setMode($mode);
            $t->setStartDate($start);
            $t->setEndDate($end);
            $t->setStatus($this->statusFromDate($start, $end));
            $t->setMaxPlayers(match ($mode) {
                Tournament::MODE_SOLO => random_int(24, 96),
                Tournament::MODE_DUO => random_int(16, 64),
                default => random_int(8, 32),
            });
            $t->setEntryFee(random_int(0, 1) ? (float) random_int(5, 30) : 0.0);
            $t->setIsAiGenerated(random_int(0, 1) === 1);

            $this->entityManager->persist($t);
            $tournaments[] = $t;
        }

        $this->entityManager->flush();

        $voucherCount = 0;
        $voucherPairs = [];
        foreach ($tournaments as $tournament) {
            if (!$tournament->isPaid()) {
                continue;
            }

            $maxForTournament = min((int) $tournament->getMaxPlayers(), count($users));
            $voucherTarget = random_int(2, max(2, min(6, $maxForTournament)));
            $voucherCount += $this->createVouchersForTournament(
                $tournament,
                $users,
                $voucherTarget,
                $voucherPairs,
                false
            );
        }

        $paidTournaments = array_values(array_filter($tournaments, static fn (Tournament $t): bool => $t->isPaid()));
        while ($voucherCount < 20 && count($paidTournaments) > 0) {
            $tournament = $paidTournaments[array_rand($paidTournaments)];
            $added = $this->createVouchersForTournament($tournament, $users, 1, $voucherPairs, false);
            if ($added === 0) {
                break;
            }
            $voucherCount += $added;
        }

        foreach ($tournaments as $tournament) {
            $targetParticipants = random_int(2, min(12, $tournament->getMaxPlayers() ?? 12));
            $pickedIds = [];

            $paidVoucherHolders = [];
            if ($tournament->isPaid()) {
                foreach ($voucherPairs as $pairKey => $enabled) {
                    if (!$enabled) {
                        continue;
                    }
                    $parts = explode(':', $pairKey);
                    if ((int) ($parts[0] ?? 0) === (int) $tournament->getId()) {
                        $paidVoucherHolders[] = (int) ($parts[1] ?? 0);
                    }
                }
                shuffle($paidVoucherHolders);
            }

            for ($i = 0; $i < $targetParticipants; $i++) {
                if ($tournament->isPaid()) {
                    $holderId = array_shift($paidVoucherHolders);
                    if ($holderId === null) {
                        break;
                    }
                    $user = $this->findUserById($users, $holderId);
                    if ($user === null) {
                        continue;
                    }
                } else {
                    $user = $users[array_rand($users)];
                }
                if (isset($pickedIds[$user->getId()])) {
                    continue;
                }
                $pickedIds[$user->getId()] = true;

                $participation = new TournamentParticipation();
                $participation->setTournament($tournament);
                $participation->setUser($user);
                $participation->setStatus(['registered', 'confirmed'][array_rand(['registered', 'confirmed'])]);
                $this->entityManager->persist($participation);
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf('Seed completed: %d tournaments created, at least %d vouchers created.', count($tournaments), $voucherCount));

        return Command::SUCCESS;
    }

    /**
     * @param User[] $users
     * @param array<string, bool> $voucherPairs
     */
    private function createVouchersForTournament(
        Tournament $tournament,
        array $users,
        int $target,
        array &$voucherPairs,
        bool $markUsed
    ): int {
        $created = 0;
        $maxPlayers = max(0, (int) $tournament->getMaxPlayers());

        foreach ($users as $user) {
            if ($created >= $target) {
                break;
            }

            $pairKey = $tournament->getId().':'.$user->getId();
            if (isset($voucherPairs[$pairKey])) {
                continue;
            }

            if ($this->countPairsForTournament($voucherPairs, (int) $tournament->getId()) >= $maxPlayers) {
                break;
            }

            $voucher = new Voucher();
            $voucher->setTournament($tournament);
            $voucher->setUser($user);
            $voucher->setAmount($tournament->getEntryFee());
            $voucher->setCode('SEED-'.strtoupper(bin2hex(random_bytes(4))).'-'.$tournament->getId());
            if ($markUsed) {
                $voucher->setUsedAt(new \DateTimeImmutable('-'.random_int(1, 12).' hours'));
            }
            $this->entityManager->persist($voucher);
            $voucherPairs[$pairKey] = true;
            $created++;
        }

        return $created;
    }

    /**
     * @param array<string, bool> $voucherPairs
     */
    private function countPairsForTournament(array $voucherPairs, int $tournamentId): int
    {
        $count = 0;
        foreach ($voucherPairs as $key => $enabled) {
            if (!$enabled) {
                continue;
            }
            if (str_starts_with($key, $tournamentId.':')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param User[] $users
     */
    private function findUserById(array $users, int $id): ?User
    {
        foreach ($users as $user) {
            if ((int) $user->getId() === $id) {
                return $user;
            }
        }

        return null;
    }

    private function randomStartDate(): \DateTime
    {
        return match (random_int(1, 3)) {
            1 => (new \DateTime('-'.random_int(10, 40).' days'))->setTime(random_int(10, 20), 0),
            2 => (new \DateTime('-'.random_int(0, 1).' days'))->setTime(random_int(10, 20), 0),
            default => (new \DateTime('+'.random_int(2, 30).' days'))->setTime(random_int(10, 20), 0),
        };
    }

    private function statusFromDate(\DateTimeInterface $start, \DateTimeInterface $end): string
    {
        $now = new \DateTime();
        if ($end < $now) {
            return 'finished';
        }
        if ($start <= $now && $end >= $now) {
            return 'live';
        }
        return 'upcoming';
    }
}
