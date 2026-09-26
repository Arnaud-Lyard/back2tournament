<?php

declare(strict_types=1);

namespace App\Authentication\User\Domain\Event;

use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Shared\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class UserCreatedEvent extends Event implements DomainEventInterface
{
    private string $userId;
    private Email $email;
    private string $verificationToken;
    private Locale $locale;

    public function __construct(string $userId, Email $email, string $verificationToken, Locale $locale)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->verificationToken = $verificationToken;
        $this->locale = $locale;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getVerificationToken(): string
    {
        return $this->verificationToken;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }
}
