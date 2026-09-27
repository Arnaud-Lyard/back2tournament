<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Model;

final class FindGameClansQuery
{
    private string $gameId;

    private int $page;

    private int $limit;

    private ?string $search;

    public function __construct(string $gameId, int $page, int $limit, string $search)
    {
        $this->gameId = $gameId;
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
        $this->search = '' === trim($search) ? null : trim($search);
    }

    public function getGameId(): string
    {
        return $this->gameId;
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

    public function getSearch(): ?string
    {
        return $this->search;
    }
}
