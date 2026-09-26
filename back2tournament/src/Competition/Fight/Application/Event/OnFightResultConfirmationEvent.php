<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnFightResultConfirmationEvent extends Event
{
    private string $game;

    private string $fight;

    public function __construct(string $game, string $fight)
    {
        $this->game = $game;
        $this->fight = $fight;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function getFight(): string
    {
        return $this->fight;
    }
}
