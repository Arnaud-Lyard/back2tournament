<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class ApiClient implements UserInterface
{
    public const NAME = 'bot';

    public const ROLE = 'ROLE_BOT';

    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getRoles(): array
    {
        return [self::ROLE];
    }

    public function getUserIdentifier(): string
    {
        return $this->name;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
