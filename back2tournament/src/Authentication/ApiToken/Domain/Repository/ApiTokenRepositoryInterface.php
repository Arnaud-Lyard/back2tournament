<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Domain\Repository;

use App\Authentication\ApiToken\Domain\Entity\ApiToken;

interface ApiTokenRepositoryInterface
{
    public function findOneByName(string $name): ?ApiToken;

    public function findOneByTokenHash(string $tokenHash): ?ApiToken;

    /** @return list<ApiToken> */
    public function findAllByCreation(): array;

    public function save(ApiToken $apiToken): void;

    public function remove(ApiToken $apiToken): void;
}
