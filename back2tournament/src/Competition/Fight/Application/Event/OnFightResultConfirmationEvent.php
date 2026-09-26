<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnFightResultConfirmationEvent extends Event
{
    private string $fight;

    private string $confirmedFight;

    public function __construct(string $fight)
    {
        $this->fight = $fight;
    }

    public function getFight(): string
    {
        return $this->fight;
    }

    /**
     * The fight once confirmed, as the handler serialized it, handed back to the controller.
     */
    public function getConfirmedFight(): string
    {
        return $this->confirmedFight;
    }

    public function setConfirmedFight(string $confirmedFight): void
    {
        $this->confirmedFight = $confirmedFight;
    }
}
