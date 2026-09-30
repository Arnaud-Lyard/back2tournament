<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Infrastructure\Repository;

use App\Authentication\ApiToken\Domain\Entity\ApiToken;
use App\Authentication\ApiToken\Domain\Repository\ApiTokenRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ApiToken> */
final class ApiTokenRepository extends ServiceEntityRepository implements ApiTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function findOneByName(string $name): ?ApiToken
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findOneByTokenHash(string $tokenHash): ?ApiToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function findAllByCreation(): array
    {
        return $this->findBy([], ['createdAt' => 'ASC', 'name' => 'ASC']);
    }

    public function save(ApiToken $apiToken): void
    {
        $this->getEntityManager()->persist($apiToken);
        $this->getEntityManager()->flush();
    }

    public function remove(ApiToken $apiToken): void
    {
        $this->getEntityManager()->remove($apiToken);
        $this->getEntityManager()->flush();
    }
}
