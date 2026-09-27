<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Infrastructure\Repository;

use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClanMember>
 */
final class ClanMemberRepository extends ServiceEntityRepository implements ClanMemberRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClanMember::class);
    }

    public function save(ClanMember $clanMember): void
    {
        $this->getEntityManager()->persist($clanMember);
        $this->getEntityManager()->flush();
    }

    public function remove(ClanMember $clanMember): void
    {
        $this->getEntityManager()->remove($clanMember);
        $this->getEntityManager()->flush();
    }

    public function countActiveByClan(array $clanIds): array
    {
        if ([] === $clanIds) {
            return [];
        }

        // Not "member": MEMBER is a DQL keyword (MEMBER OF), refused as an alias.
        /** @var list<array{clan: string, members: int|string}> $rows */
        $rows = $this->createQueryBuilder('clanMember')
            ->select('clanMember.clan AS clan, COUNT(clanMember.id) AS members')
            ->andWhere('clanMember.clan IN (:clanIds)')
            ->andWhere('clanMember.status = :active')
            ->setParameter('clanIds', $clanIds)
            ->setParameter('active', ClanMemberStatus::ACTIVE)
            ->groupBy('clanMember.clan')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys($clanIds, 0);
        foreach ($rows as $row) {
            $counts[$row['clan']] = (int) $row['members'];
        }

        return $counts;
    }
}
