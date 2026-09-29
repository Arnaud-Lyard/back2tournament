<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class FindFightsQuery
{
    private string $status;

    private ?string $gameId;

    private ?string $search;

    private int $page;

    private int $limit;

    public function __construct(string $status, ?string $gameId, ?string $search, int $page, int $limit)
    {
        $this->status = '' === $status ? 'all' : $status;
        $this->gameId = '' === $gameId ? null : $gameId;
        $search = null === $search ? '' : trim($search);
        $this->search = '' === $search ? null : $search;
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getGameId(): ?string
    {
        return $this->gameId;
    }

    public function getSearch(): ?string
    {
        return $this->search;
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
