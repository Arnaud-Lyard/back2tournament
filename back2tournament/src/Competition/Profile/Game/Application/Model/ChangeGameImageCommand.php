<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Model;

/**
 * Gives a game its picture, from the bytes of an uploaded image, or takes it
 * away.
 */
final class ChangeGameImageCommand
{
    private string $gameId;
    private ?string $image;

    /**
     * @param string|null $image the uploaded bytes; null takes the picture away
     */
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
