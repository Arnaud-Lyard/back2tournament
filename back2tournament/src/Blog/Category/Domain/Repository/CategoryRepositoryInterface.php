<?php

declare(strict_types=1);

namespace App\Blog\Category\Domain\Repository;

use App\Blog\Category\Domain\Entity\Category;

interface CategoryRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<Category> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function save(Category $comment): void;
}
