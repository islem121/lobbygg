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
    #[ORM\Column(name: 'sponsor_id')]
    private ?int $id = null;

    // DB column is company_name.
    #[ORM\Column(name: 'company_name', length: 180)]
    #[Assert\NotBlank(message: 'Le nom de la sociÃ©tÃ© est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom de la sociÃ©tÃ© doit faire au moins {{ limit }} caractÃ¨res.',
        maxMessage: 'Le nom de la sociÃ©tÃ© ne peut pas dÃ©passer {{ limit }} caractÃ¨res.'
    )]
    private ?string $nomSociete = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'La description doit faire au moins {{ limit }} caractÃ¨res.'
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire.')]
    #[Assert\Positive(message: 'Le montant doit Ãªtre un nombre positif.')]
    #[Assert\Type(type: 'float', message: 'Le montant doit Ãªtre un nombre.')]
    private ?float $amount = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le type de cible est obligatoire.')]
    #[Assert\Choice(choices: ['client', 'tournament'], message: 'Le type de cible choisi est invalide.')]
    private ?string $targetType = null; // 'client' ou 'tournament'

    // Reuse existing logo column as storage for uploaded file reference.
    #[ORM\Column(name: 'logo', length: 255, nullable: true)]
    private ?string $dossier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $sponsor = null;

    #[ORM\Column(nullable: true)]
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
