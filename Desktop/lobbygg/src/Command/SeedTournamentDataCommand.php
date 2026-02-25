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
        foreach ($tournaments as $tournament) {
            if (!$tournament->isPaid()) {
                continue;
            }
            for ($i = 0; $i < 4; $i++) {
                $user = $users[array_rand($users)];
                $voucher = new Voucher();
                $voucher->setTournament($tournament);
                $voucher->setUser($user);
                $voucher->setAmount($tournament->getEntryFee());
                $voucher->setCode('SEED-'.strtoupper(bin2hex(random_bytes(3))).'-'.$tournament->getId());
                if ($i < 2) {
                    $voucher->setUsedAt(new \DateTime('-'.random_int(1, 6).' day'));
                }
                $this->entityManager->persist($voucher);
                $voucherCount++;
            }
        }

        while ($voucherCount < 20) {
            $tournament = $tournaments[array_rand($tournaments)];
            $user = $users[array_rand($users)];
            $voucher = new Voucher();
            $voucher->setTournament($tournament);
            $voucher->setUser($user);
            $voucher->setAmount($tournament->getEntryFee());
            $voucher->setCode('SEED-'.strtoupper(bin2hex(random_bytes(3))).'-'.$tournament->getId());
            $this->entityManager->persist($voucher);
            $voucherCount++;
        }

        foreach ($tournaments as $tournament) {
            $targetParticipants = random_int(2, min(12, $tournament->getMaxPlayers() ?? 12));
            $pickedIds = [];
            for ($i = 0; $i < $targetParticipants; $i++) {
                $user = $users[array_rand($users)];
                if (isset($pickedIds[$user->getId()])) {
                    continue;
                }
                $pickedIds[$user->getId()] = true;

                if ($tournament->isPaid()) {
                    $voucher = new Voucher();
                    $voucher->setTournament($tournament);
                    $voucher->setUser($user);
                    $voucher->setAmount($tournament->getEntryFee());
                    $voucher->setCode('SEED-JOIN-'.strtoupper(bin2hex(random_bytes(2))).'-'.$tournament->getId());
                    $voucher->setUsedAt(new \DateTime('-'.random_int(1, 10).' hours'));
                    $this->entityManager->persist($voucher);
                    $voucherCount++;
                }

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
