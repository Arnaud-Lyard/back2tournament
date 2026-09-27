<?php

declare(strict_types=1);

namespace App\Authentication\User\Domain\Entity;

use App\Authentication\User\Domain\Event\UserAvatarChangedEvent;
use App\Authentication\User\Domain\Event\UserCreatedEvent;
use App\Media\Image\Domain\Attribute\StoredImage;
use App\Shared\Aggregate\AggregateRoot;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\Uuid;

class User extends AggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    private string $id;

    private string $email;

    private string $username;

    private array $roles = [];

    #[Ignore]
    private string $password;

    private bool $verified;

    #[Ignore]
    private ?string $verificationToken = null;

    /**
     * The key of the user's picture in the image storage; null when they have none.
     */
    #[StoredImage]
    private ?string $avatar = null;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    // Required by UserInterface, but it only duplicates username and belongs to
    // Symfony's security plumbing, not to the API contract.
    #[Ignore]
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    #[Ignore]
    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): self
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * Gives the user their picture, as the key of the stored image, or takes
     * it away with null.
     */
    public static function changeAvatar(User $user, ?string $avatar): void
    {
        $user->avatar = $avatar;

        $user->recordDomainEvent(new UserAvatarChangedEvent($user->id));
    }

    public function verifyEmail(): self
    {
        $this->verified = true;
        $this->verificationToken = null;

        return $this;
    }

    public static function registerUser(
        Email $email,
        Username $username,
        array $roles,
        Password $password,
        Locale $locale,
    ): self
    {
        $userId = Uuid::v4()->toString();
        $verificationToken = Uuid::v4()->toString();

        $user = new User($userId);
        $user->setEmail($email->getValue());
        $user->setUsername($username->getValue());
        $user->setRoles($roles);
        $user->setPassword($password->getValue());
        $user->setVerified(false);

        $user->setVerificationToken($verificationToken);

        $user->recordDomainEvent(new UserCreatedEvent($userId, $email, $verificationToken, $locale));

        return $user;
    }
}
