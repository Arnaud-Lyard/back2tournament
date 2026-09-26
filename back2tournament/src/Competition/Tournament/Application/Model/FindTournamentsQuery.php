<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Model;

final class FindTournamentsQuery
{
    private ?string $gameId;

    private ?string $status;

    private int $page;

    private int $limit;

    public function __construct(?string $gameId, ?string $status, int $page, int $limit)
    {
        $this->gameId = '' === trim($gameId ?? '') ? null : trim($gameId);
        $this->status = '' === trim($status ?? '') ? null : trim($status);
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
    }

    public function getGameId(): ?string
    {
        return $this->gameId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
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
