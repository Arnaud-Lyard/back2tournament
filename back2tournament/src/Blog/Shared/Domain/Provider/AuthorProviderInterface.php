<?php

declare(strict_types=1);

namespace App\Blog\Shared\Domain\Provider;

/**
 * How the blog names the users behind its articles and comments.
 */
interface AuthorProviderInterface
{
    /**
     * @param list<string> $userIds
     *
     * @return array<string, string> the username of each user, keyed by user id; an unknown id is left out
     */
    public function usernames(array $userIds): array;
}
