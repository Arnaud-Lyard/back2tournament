<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Model;

final class CreateClanCommand
{
    private string $game;

    private string $name;

    private string $tag;

    public function __construct(string $game, string $name, string $tag)
    {
        $this->game = $game;
        $this->name = $name;
        $this->tag = $tag;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTag(): string
    {
        return $this->tag;
    }
}
