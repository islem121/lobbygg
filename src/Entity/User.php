<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris.')]
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

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 30,
        minMessage: 'Le nom d\'utilisateur doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_]+$/',
        message: 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores.'
    )]
    private ?string $username = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email "{{ value }}" n\'est pas valide.')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La biographie ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $bio = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9]{10}$/',
        message: 'Le numéro de téléphone doit contenir exactement 10 chiffres.'
    )]
    private ?string $telephone = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le rôle est obligatoire.')]
    #[Assert\Choice(
        choices: [self::ROLE_CLIENT, self::ROLE_ADMIN, self::ROLE_SPONSOR],
        message: 'Choisissez un rôle valide.'
    )]
    private ?string $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
        message: 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.'
    )]
    private ?string $nom = null;

    #[ORM\Column(name: 'datenaissance', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire.')]
    #[Assert\Type('\DateTimeInterface', message: 'La date de naissance doit être une date valide.')]
    #[Assert\LessThanOrEqual(
        '-13 years',
        message: 'Vous devez avoir au moins 13 ans.'
    )]
    #[Assert\GreaterThanOrEqual(
        '-70 years', 
        message: 'Vous devez avoir moins de 70 ans.'
    )]
    private ?\DateTimeInterface $dateNaissance = null;

    // Propriété non persistée pour la validation des CGU
    #[Assert\IsTrue(message: 'Vous devez accepter les conditions générales d\'utilisation.')]
    private bool $cguAccepted = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->role = self::ROLE_CLIENT;
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

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getCguAccepted(): bool
    {
        return $this->cguAccepted;
    }

    public function setCguAccepted(bool $cguAccepted): static
    {
        $this->cguAccepted = $cguAccepted;
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
        return (string) $this->email;
    }

    public function getSalt(): ?string
    {
        // Pas nécessaire avec bcrypt/argon2i
        return null;
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

    /**
     * Validation personnalisée pour vérifier l'âge
     * (Utilisée en complément des contraintes Assert)
     */
    public function isAgeValid(): bool
    {
        $age = $this->getAge();
        return $age !== null && $age >= 13 && $age <= 70;
    }

    /**
     * Validation personnalisée pour vérifier le téléphone
     * (Utilisée en complément des contraintes Assert)
     */
    public function isTelephoneValid(): bool
    {
        if ($this->telephone === null) {
            return true; // Null est autorisé (nullable: true)
        }
        
        return preg_match('/^[0-9]{10}$/', $this->telephone) === 1;
    }

    // Pour affichage
    public function __toString(): string
    {
        return $this->nom ?: $this->username ?: 'Utilisateur #' . $this->id;
    }
}