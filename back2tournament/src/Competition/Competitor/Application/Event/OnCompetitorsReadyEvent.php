<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class OnCompetitorsReadyEvent extends Event
{
    private string $competitorOne;
    private string $competitorTwo;

    public function __construct(string $competitorOne, string $competitorTwo)
    {
        $this->competitorOne = $competitorOne;
        $this->competitorTwo = $competitorTwo;
    }

    public function getCompetitorOne(): string
    {
        return $this->competitorOne;
    }

    public function getCompetitorTwo(): string
    {
        return $this->competitorTwo;
    }
}
