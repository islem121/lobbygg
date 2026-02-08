<?php

namespace App\Entity;

use App\Repository\SponsorshipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SponsorshipRepository::class)]
class Sponsorship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $targetType = null;

    #[ORM\Column]
    private ?int $targetId = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?bool $showLogo = null;

    #[ORM\Column]
    private ?bool $showAd = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(int $targetId): static
    {
        $this->targetId = $targetId;

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

    public function isShowLogo(): ?bool
    {
        return $this->showLogo;
    }

    public function setShowLogo(bool $showLogo): static
    {
        $this->showLogo = $showLogo;

        return $this;
    }

    public function isShowAd(): ?bool
    {
        return $this->showAd;
    }

    public function setShowAd(bool $showAd): static
    {
        $this->showAd = $showAd;

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
