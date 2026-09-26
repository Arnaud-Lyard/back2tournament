<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Model;

final class FindPendingUserFightResultsQuery
{
    private int $page;

    private int $limit;

    public function __construct(int $page, int $limit)
    {
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
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
