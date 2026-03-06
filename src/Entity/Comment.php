<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'comment_id')]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le commentaire ne peut pas être vide.')]
    #[Assert\Length(min: 2, minMessage: 'Le commentaire doit faire au moins {{ limit }} caractères.')]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'up_votes')]
    private int $upVotes = 0;

    #[ORM\Column(name: 'down_votes')]
    private int $downVotes = 0;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'L\'auteur est obligatoire.')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'post_id', nullable: false)]
    #[Assert\NotBlank(message: 'L\'article est obligatoire.')]
    private ?Post $post = null;

    #[ORM\OneToMany(mappedBy: 'comment', targetEntity: CommentReaction::class, orphanRemoval: true)]
    private Collection $reactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->reactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
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

    public function getUpVotes(): int
    {
        return $this->upVotes;
    }

    public function setUpVotes(int $upVotes): static
    {
        $this->upVotes = $upVotes;
        return $this;
    }

    public function getDownVotes(): int
    {
        return $this->downVotes;
    }

    public function setDownVotes(int $downVotes): static
    {
        $this->downVotes = $downVotes;
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

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;
        return $this;
    }

    /**
     * @return Collection<int, CommentReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(CommentReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setComment($this);
        }
        return $this;
    }

    public function removeReaction(CommentReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            // set the owning side to null (unless already changed)
            if ($reaction->getComment() === $this) {
                $reaction->setComment(null);
            }
        }
        return $this;
    }
}
