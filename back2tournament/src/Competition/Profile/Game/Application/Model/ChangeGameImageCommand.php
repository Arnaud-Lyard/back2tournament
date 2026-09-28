<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Model;

final class ChangeGameImageCommand
{
    private string $gameId;
    private ?string $image;

    public function __construct(string $gameId, ?string $image)
    {
        $this->gameId = $gameId;
        $this->image = $image;
    }

    public function getGameId(): string
    {
        return $this->gameId;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }
}
