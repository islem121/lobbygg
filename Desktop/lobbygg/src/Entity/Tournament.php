<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Table(name: 'tournament')]
class Tournament
{
    public const TYPE_FREE = 'FREE';
    public const TYPE_PAID = 'PAID';

    public const MODE_SOLO = 'solo';
    public const MODE_DUO = 'duo';
    public const MODE_SQUAD = 'squad';

    public const MODES = [
        self::MODE_SOLO,
        self::MODE_DUO,
        self::MODE_SQUAD,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'title', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(name: 'start_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'max_players')]
    private ?int $maxPlayers = null;

    #[ORM\Column(length: 20)]
    private ?string $mode = self::MODE_SOLO;

    #[ORM\Column(name: 'entry_fee', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $entryFee = '0.00';

    #[ORM\Column(name: 'is_ai_generated', options: ['default' => 0])]
    private bool $isAiGenerated = false;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, TournamentParticipation>
     */
    #[ORM\OneToMany(mappedBy: 'tournament', targetEntity: TournamentParticipation::class, orphanRemoval: true)]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->status = 'upcoming';
        $this->mode = self::MODE_SOLO;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    /**
     * Backward compatible alias for systems expecting "name".
     */
    public function getName(): ?string
    {
        return $this->getTitle();
    }

    public function setName(string $name): static
    {
        return $this->setTitle($name);
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        if (!in_array($mode, self::MODES, true)) {
            throw new \InvalidArgumentException('Invalid tournament mode.');
        }

        $this->mode = $mode;

        return $this;
    }

    public function getEntryFee(): float
    {
        return (float) $this->entryFee;
    }

    public function setEntryFee(float $entryFee): static
    {
        $entryFee = max(0.0, $entryFee);
        $this->entryFee = number_format($entryFee, 2, '.', '');

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->getEntryFee() > 0.0;
    }

    public function getType(): string
    {
        return $this->isPaid() ? self::TYPE_PAID : self::TYPE_FREE;
    }

    public function setType(string $type): static
    {
        $normalized = strtoupper(trim($type));
        if ($normalized === self::TYPE_FREE) {
            return $this->setEntryFee(0.0);
        }

        if ($normalized === self::TYPE_PAID) {
            if ($this->getEntryFee() <= 0.0) {
                $this->setEntryFee(1.0);
            }

            return $this;
        }

        throw new \InvalidArgumentException('Invalid tournament type.');
    }

    /**
     * Dynamic prize pool calculation:
     * PrizePool = entryFee * numberOfParticipants
     */
    public function getPrizePool(): ?float
    {
        if (!$this->isPaid()) {
            return null;
        }

        return round($this->getEntryFee() * $this->participations->count(), 2);
    }

    public function isAiGenerated(): bool
    {
        return $this->isAiGenerated;
    }

    public function setIsAiGenerated(bool $isAiGenerated): static
    {
        $this->isAiGenerated = $isAiGenerated;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, TournamentParticipation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(TournamentParticipation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setTournament($this);
        }

        return $this;
    }

    public function removeParticipation(TournamentParticipation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getTournament() === $this) {
                $participation->setTournament(null);
            }
        }

        return $this;
    }
}
