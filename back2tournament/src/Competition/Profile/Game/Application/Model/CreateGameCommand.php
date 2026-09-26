<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Model;

final class CreateGameCommand
{
    private string $title;
    private string $user;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): void
    {
        $this->user = $user;
    }
}
