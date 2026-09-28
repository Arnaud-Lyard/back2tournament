<?php

declare(strict_types=1);

namespace App\Blog\Article\Domain\Enum;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}
