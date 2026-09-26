<?php

declare(strict_types=1);

namespace App\Blog\Article\Domain\Repository;

use App\Blog\Article\Domain\Entity\Article;

interface ArticleRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * @return list<Article>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * @return list<Article>
     */
    public function findPage(?string $search, ?string $categoryId, int $limit, int $offset): array;

    public function countPage(?string $search, ?string $categoryId): int;

    public function save(Article $article): void;
}
