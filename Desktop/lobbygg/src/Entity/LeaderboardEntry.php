<?php

namespace App\Entity;

use App\Repository\LeaderboardEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeaderboardEntryRepository::class)]
#[ORM\Table(name: 'leaderboard_entry')]
#[ORM\UniqueConstraint(name: 'uniq_leaderboard_tournament_user', columns: ['tournament_id', 'user_id'])]
class LeaderboardEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(name: 'tournament_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $wins = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $losses = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(name: 'rank_position', options: ['default' => 0])]
    private int $rank = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function setWins(int $wins): static
    {
        $this->wins = max(0, $wins);

        return $this;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function setLosses(int $losses): static
    {
        $this->losses = max(0, $losses);

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = max(0, $points);

        return $this;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setRank(int $rank): static
    {
        $this->rank = max(1, $rank);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

