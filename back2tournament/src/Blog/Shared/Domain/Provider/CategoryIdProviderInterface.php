<?php

declare(strict_types=1);

namespace App\Blog\Shared\Domain\Provider;

interface CategoryIdProviderInterface
{
    public function bySlug(string $slug): string;
}
