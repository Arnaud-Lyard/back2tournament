<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Model;

final class CreatePlayerCommand
{
    private string $battletag;

    private string $user;

    private string $game;

    public function getBattletag(): string
    {
        return $this->battletag;
    }

    public function setBattletag(string $battletag): void
    {
        $this->battletag = $battletag;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function setGame(string $game): void
    {
        $this->game = $game;
    }
}
