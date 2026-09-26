<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnPlayerCreationRequestedEvent extends Event
{
    private string $battletag;

    private string $game;

    public function __construct(string $battletag, string $game)
    {
        $this->battletag = $battletag;
        $this->game = $game;
    }

    public function getBattletag(): string
    {
        return $this->battletag;
    }


    public function getGame(): string
    {
        return $this->game;
    }
}
