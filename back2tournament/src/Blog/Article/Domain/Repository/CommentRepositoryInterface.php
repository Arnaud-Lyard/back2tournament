<?php

declare(strict_types=1);

namespace App\Blog\Article\Domain\Repository;

use App\Blog\Article\Domain\Entity\Comment;

interface CommentRepositoryInterface
{
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    public function save(Comment $comment): void;
}
