<?php

declare(strict_types=1);

namespace App\Blog\Article\Application\Model;

final class FindArticlesQuery
{
    private int $page;

    private int $limit;

    private ?string $search;

    private ?string $categorySlug;

    public function __construct(int $page, int $limit, ?string $search = null, ?string $categorySlug = null)
    {
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
        $this->search = '' === trim($search ?? '') ? null : trim($search);
        $this->categorySlug = '' === trim($categorySlug ?? '') ? null : trim($categorySlug);
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

    public function getCategorySlug(): ?string
    {
        return $this->categorySlug;
    }
}
