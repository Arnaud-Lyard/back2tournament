<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

/**
 * The settled results of a player profile, or of a clan's teams, newest first.
 */
final class FindResultHistoryQuery
{
    private ?string $playerId;

    private ?string $clanId;

    private int $page;

    private int $limit;

    private function __construct(?string $playerId, ?string $clanId, int $page, int $limit)
    {
        $this->playerId = $playerId;
        $this->clanId = $clanId;
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
    }

    public static function ofPlayer(string $playerId, int $page, int $limit): self
    {
        return new self($playerId, null, $page, $limit);
    }

    public static function ofClan(string $clanId, int $page, int $limit): self
    {
        return new self(null, $clanId, $page, $limit);
    }

    public function getPlayerId(): ?string
    {
        return $this->playerId;
    }

    public function getClanId(): ?string
    {
        return $this->clanId;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
