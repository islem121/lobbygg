<?php

namespace App\Service;

use App\Entity\Tournament;
use App\Entity\User;
use App\Entity\Voucher;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;

class TournamentVoucherService
{
    public function __construct(
        private readonly VoucherRepository $voucherRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Dynamic tournament financial snapshot.
     *
     * PrizePool is not stored in DB.
     * PrizePool = entryFee * numberOfSoldVouchers
     *
     * @return array{
     *     entryFee: float,
     *     soldVouchers: int,
     *     maxPlayers: int,
     *     remainingVouchers: int,
     *     prizePool: float|null,
     *     progressPercentage: float,
     *     isPaid: bool,
     *     isSoldOut: bool
     * }
     */
    public function getPrizePoolSnapshot(Tournament $tournament): array
    {
        $soldVouchers = $this->voucherRepository->countByTournament($tournament);
        $maxPlayers = max(0, (int) $tournament->getMaxPlayers());
        $entryFee = (float) $tournament->getEntryFee();
        $isPaid = $tournament->isPaid();
        $remainingVouchers = max(0, $maxPlayers - $soldVouchers);
        $progressPercentage = $maxPlayers > 0
            ? min(100.0, round(($soldVouchers / $maxPlayers) * 100, 2))
            : 0.0;

        return [
            'entryFee' => $entryFee,
            'soldVouchers' => $soldVouchers,
            'maxPlayers' => $maxPlayers,
            'remainingVouchers' => $remainingVouchers,
            'prizePool' => $isPaid ? round($entryFee * $soldVouchers, 2) : null,
            'progressPercentage' => $progressPercentage,
            'isPaid' => $isPaid,
            'isSoldOut' => $maxPlayers > 0 && $soldVouchers >= $maxPlayers,
        ];
    }

    /**
     * Purchase a voucher for a paid tournament.
     *
     * @return array{success: bool, message: string, voucherCode?: string}
     */
    public function purchaseVoucher(User $user, Tournament $tournament): array
    {
        if (!$tournament->isPaid()) {
            return ['success' => false, 'message' => 'Vouchers are only available for paid tournaments.'];
        }

        $existingVoucher = $this->voucherRepository->findAnyForUserAndTournament($user, $tournament);
        if ($existingVoucher !== null) {
            return [
                'success' => false,
                'message' => 'You already purchased a voucher for this tournament.',
                'voucherCode' => (string) $existingVoucher->getCode(),
            ];
        }

        $snapshot = $this->getPrizePoolSnapshot($tournament);
        if ($snapshot['soldVouchers'] >= $snapshot['maxPlayers']) {
            return ['success' => false, 'message' => 'No vouchers remaining for this tournament.'];
        }

        $voucher = new Voucher();
        $voucher->setUser($user);
        $voucher->setTournament($tournament);
        $voucher->setAmount($snapshot['entryFee']);
        $voucher->setCode(sprintf('VCH-%d-%d-%s', (int) $tournament->getId(), (int) $user->getId(), strtoupper(bin2hex(random_bytes(3)))));
        $voucher->setPurchasedAt(new \DateTimeImmutable());

        $this->entityManager->persist($voucher);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => sprintf('Voucher purchased successfully for %.2f.', $snapshot['entryFee']),
            'voucherCode' => (string) $voucher->getCode(),
        ];
    }
}

