<?php

namespace App\Entity;

use App\Repository\TournamentMatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentMatchRepository::class)]
#[ORM\Table(name: 'tournament_match')]
class TournamentMatch
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_FINISHED = 'finished';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(name: 'tournament_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Tournament $tournament = null;

    #[ORM\Column(name: 'round_number', options: ['default' => 1])]
    private int $roundNumber = 1;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'player_one_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $playerOne = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'player_two_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $playerTwo = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'winner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $winner = null;

    #[ORM\Column(name: 'score_one', options: ['default' => 0])]
    private int $scoreOne = 0;

    #[ORM\Column(name: 'score_two', options: ['default' => 0])]
    private int $scoreTwo = 0;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_PENDING])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(Tournament $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getRoundNumber(): int
    {
        return $this->roundNumber;
    }

    public function setRoundNumber(int $roundNumber): static
    {
        $this->roundNumber = max(1, $roundNumber);

        return $this;
    }

    public function getPlayerOne(): ?User
    {
        return $this->playerOne;
    }

    public function setPlayerOne(User $playerOne): static
    {
        $this->playerOne = $playerOne;

        return $this;
    }

    public function getPlayerTwo(): ?User
    {
        return $this->playerTwo;
    }

    public function setPlayerTwo(User $playerTwo): static
    {
        $this->playerTwo = $playerTwo;

        return $this;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): static
    {
        $this->winner = $winner;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getScoreOne(): int
    {
        return $this->scoreOne;
    }

    public function setScoreOne(int $scoreOne): static
    {
        $this->scoreOne = max(0, $scoreOne);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getScoreTwo(): int
    {
        return $this->scoreTwo;
    }

    public function setScoreTwo(int $scoreTwo): static
    {
        $this->scoreTwo = max(0, $scoreTwo);
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}

