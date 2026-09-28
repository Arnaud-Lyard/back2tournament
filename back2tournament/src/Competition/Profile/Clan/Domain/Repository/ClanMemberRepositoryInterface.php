<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Repository;

use App\Competition\Profile\Clan\Domain\Entity\ClanMember;

interface ClanMemberRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /** @return list<ClanMember> */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * @param list<string> $clanIds
     *
     * @return array<string, int>
     */
    public function countActiveByClan(array $clanIds): array;

    public function save(ClanMember $clanMember): void;

    public function remove(ClanMember $clanMember): void;
}
