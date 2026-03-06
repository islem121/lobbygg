<?php

namespace App\Entity;

use App\Repository\TournamentParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentParticipationRepository::class)]
#[ORM\Table(name: 'tournament_participation')]
#[ORM\UniqueConstraint(name: 'uniq_tournament_user', columns: ['tournament_id', 'user_id'])]
class TournamentParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'registration_date', type: 'datetime')]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Tournament::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'tournament_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Tournament $tournament = null;

    public function __construct()
    {
        $this->registrationDate = new \DateTime();
        $this->status = 'registered';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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
}
