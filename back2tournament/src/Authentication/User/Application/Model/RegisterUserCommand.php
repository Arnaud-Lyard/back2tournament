<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Model;

final class RegisterUserCommand
{
    private string $email;
    private string $username;
    private string $password;
    private string $passwordConfirmation;
    private string $locale;

    public function __construct(
        string $email,
        string $username,
        string $password,
        string $passwordConfirmation,
        string $locale,
    ) {
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
        $this->locale = $locale;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
