<?php

declare(strict_types=1);

namespace App\Blog\Shared\Domain\Provider;

interface AuthorProviderInterface
{
    /**
     * @param list<string> $userIds
     *
     * @return array<string, string>
     */
    public function usernames(array $userIds): array;
}
