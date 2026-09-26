<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Model;

final class VerifyEmailCommand
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
