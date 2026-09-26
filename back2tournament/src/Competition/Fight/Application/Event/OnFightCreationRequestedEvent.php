<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnFightCreationRequestedEvent extends Event
{
    private string $playerOne;

    private string $playerTwo;

    public function __construct(string $playerOne, string $playerTwo)
    {
        $this->playerOne = $playerOne;
        $this->playerTwo = $playerTwo;
    }

    public function getPlayerOne(): string
    {
        return $this->playerOne;
    }

    public function getPlayerTwo(): string
    {
        return $this->playerTwo;
    }
}
