<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Model;

final class CreateGameCommand
{
    private string $title;

    /**
     * @var list<int>
     */
    private array $teamSizes;

    /**
     * @param list<int> $teamSizes
     */
    public function __construct(string $title, array $teamSizes)
    {
        $this->title = $title;
        $this->teamSizes = $teamSizes;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return list<int>
     */
    public function getTeamSizes(): array
    {
        return $this->teamSizes;
    }
}
