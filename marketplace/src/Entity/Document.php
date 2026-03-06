<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le client est obligatoire.')]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: Sponsor::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'offre est obligatoire.')]
    private ?Sponsor $offer = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le message est obligatoire.')]
    #[Assert\Length(
        min: 5,
        minMessage: 'Le message doit faire au moins {{ limit }} caractères.'
    )]
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La motivation est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'La motivation doit faire au moins {{ limit }} caractères.'
    )]
    private ?string $motivation = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(choices: ['en attente', 'accepté', 'refusé'], message: 'Le statut choisi est invalide.')]
    private ?string $status = 'en attente';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomClient = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailClient = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dossier = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'en attente';
    }

    public function getNomClient(): ?string
    {
        return $this->nomClient;
    }

    public function setNomClient(?string $nomClient): static
    {
        $this->nomClient = $nomClient;
        return $this;
    }

    public function getEmailClient(): ?string
    {
        return $this->emailClient;
    }

    public function setEmailClient(?string $emailClient): static
    {
        $this->emailClient = $emailClient;
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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getOffer(): ?Sponsor
    {
        return $this->offer;
    }

    public function setOffer(?Sponsor $offer): static
    {
        $this->offer = $offer;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(string $motivation): static
    {
        $this->motivation = $motivation;
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
}
