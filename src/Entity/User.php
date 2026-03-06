<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Constantes pour les rôles
    public const ROLE_CLIENT = 'client';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SPONSOR = 'sponsor';
    
    public const AVAILABLE_ROLES = [
        self::ROLE_CLIENT,
        self::ROLE_ADMIN,
        self::ROLE_SPONSOR,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est obligatoire.')]
    #[Assert\Length(min: 3, minMessage: 'Le nom d\'utilisateur doit faire au moins {{ limit }} caractères.')]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email "{{ value }}" n\'est pas un email valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.', groups: ['registration'])]
    #[Assert\Length(min: 6, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.')]
    private ?string $password = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^\+?[0-9]{8,15}$/', message: 'Le numéro de téléphone n\'est pas valide.')]
    private ?string $telephone = null;

    #[ORM\Column(length: 20)]
    private ?string $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $prenom = null;

    #[ORM\Column(name: 'datenaissance', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\LessThan("today", message: 'La date de naissance doit être dans le passé.')]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PostReaction::class, orphanRemoval: true)]
    private Collection $postReactions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CommentReaction::class, orphanRemoval: true)]
    private Collection $commentReactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->role = self::ROLE_CLIENT;
        $this->notifications = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->postReactions = new ArrayCollection();
        $this->commentReactions = new ArrayCollection();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @return Collection<int, PostReaction>
     */
    public function getPostReactions(): Collection
    {
        return $this->postReactions;
    }

    /**
     * @return Collection<int, CommentReaction>
     */
    public function getCommentReactions(): Collection
    {
        return $this->commentReactions;
    }

    // ==================== GETTERS & SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        if (!in_array($role, self::AVAILABLE_ROLES)) {
            throw new \InvalidArgumentException(sprintf(
                'Rôle "%s" invalide. Rôles valides: %s',
                $role,
                implode(', ', self::AVAILABLE_ROLES)
            ));
        }
        
        $this->role = $role;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
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

    public function getRoles(): array
    {
        $roles = ['ROLE_USER']; // Rôle de base toujours présent
        
        if ($this->role) {
            $roles[] = 'ROLE_' . strtoupper($this->role);
        }
        
        return array_unique($roles);
    }

    /**
     * Méthode requise par UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires sensibles, effacez-les ici
    }

    /**
     * Méthode requise par UserInterface
     * Retourne l'identifiant utilisé pour l'authentification
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email; // ou $this->username selon votre choix
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSponsor(): bool
    {
        return $this->role === self::ROLE_SPONSOR;
    }

    public function setAsClient(): static
    {
        $this->role = self::ROLE_CLIENT;
        return $this;
    }

    public function setAsAdmin(): static
    {
        $this->role = self::ROLE_ADMIN;
        return $this;
    }

    public function setAsSponsor(): static
    {
        $this->role = self::ROLE_SPONSOR;
        return $this;
    }

    public function getAge(): ?int
    {
        if (!$this->dateNaissance) {
            return null;
        }
        
        $now = new \DateTime();
        $interval = $this->dateNaissance->diff($now);
        
        return $interval->y;
    }

    public function getDateNaissanceFormatted(string $format = 'd/m/Y'): ?string
    {
        if (!$this->dateNaissance) {
            return null;
        }
        
        return $this->dateNaissance->format($format);
    }

    // Pour affichage
    public function __toString(): string
    {
        return $this->nom ?: $this->username;
    }

   
}
