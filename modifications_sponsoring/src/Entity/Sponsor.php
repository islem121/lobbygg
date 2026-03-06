<?php

namespace App\Entity;

use App\Repository\SponsorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
#[ORM\Table(name: 'sponsor')]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la société est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom de la société doit faire au moins {{ limit }} caractères.',
        maxMessage: 'Le nom de la société ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nomSociete = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'La description doit faire au moins {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le montant est obligatoire.')]
    #[Assert\Positive(message: 'Le montant doit être un nombre positif.')]
    #[Assert\Type(type: 'float', message: 'Le montant doit être un nombre.')]
    private ?float $amount = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de cible est obligatoire.')]
    #[Assert\Choice(choices: ['client', 'tournament'], message: 'Le type de cible choisi est invalide.')]
    private ?string $targetType = null; // 'client' ou 'tournament'

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dossier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sponsor = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSociete(): ?string
    {
        return $this->nomSociete;
    }

    public function setNomSociete(string $nomSociete): static
    {
        $this->nomSociete = $nomSociete;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): static
    {
        $this->targetType = $targetType;
        return $this;
    }

    public function getDossier(): ?string
    {
        return $this->dossier;
    }

    public function setDossier(?string $dossier): static
    {
        $this->dossier = $dossier;
        return $this;
    }

    public function getSponsor(): ?User
    {
        return $this->sponsor;
    }

    public function setSponsor(?User $sponsor): static
    {
        $this->sponsor = $sponsor;
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
