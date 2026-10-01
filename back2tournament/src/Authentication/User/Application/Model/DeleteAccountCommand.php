<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Model;

final class DeleteAccountCommand
{
    private string $password;

    public function __construct(#[\SensitiveParameter] string $password)
    {
        $this->password = $password;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
