<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnGameVerifiedEvent extends Event
{
    private string $battletag;
    private string $user;
    private string $game;

    public function __construct(string $battletag, string $user, string $game)
    {
        $this->battletag = $battletag;
        $this->user = $user;
        $this->game = $game;
    }

    public function getBattletag(): string
    {
        return $this->battletag;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getGame(): string
    {
        return $this->game;
    }

}
