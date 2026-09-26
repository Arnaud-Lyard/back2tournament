<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnGameCreationAdminVerifiedEvent extends Event
{
    private string $title;
    private string $user;

    public function __construct(string $title, string $user)
    {
        $this->title = $title;
        $this->user = $user;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUser(): string
    {
        return $this->user;
    }
}
