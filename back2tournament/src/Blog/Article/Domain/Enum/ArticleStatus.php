<?php

declare(strict_types=1);

namespace App\Blog\Article\Domain\Enum;

/**
 * A draft is read in the backoffice only; a published article is public.
 */
enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}
