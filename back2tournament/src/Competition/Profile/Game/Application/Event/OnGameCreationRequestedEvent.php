<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnGameCreationRequestedEvent extends Event
{
    private string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

}
