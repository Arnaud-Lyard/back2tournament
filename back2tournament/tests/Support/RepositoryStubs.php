<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\ValueObject\AggregateRootId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Repository doubles that answer findOneBy / findBy / count from a fixed set of
 * entities, the way Doctrine would: each criterion is read through the entity's
 * getter, and a list criterion means "one of these".
 */
trait RepositoryStubs
{
    /**
     * @template T of object
     *
     * @param class-string<T> $interface
     * @param list<object>    $entities
     *
     * @return T&Stub
     */
    private function repositoryStub(string $interface, array $entities): object
    {
        $repository = $this->createStub($interface);
        $this->answerFrom($repository, $interface, $entities);

        return $repository;
    }

    /**
     * The same, as a mock, for a test that also sets expectations on writes.
     *
     * @template T of object
     *
     * @param class-string<T> $interface
     * @param list<object>    $entities
     *
     * @return T&MockObject
     */
    private function repositoryMock(string $interface, array $entities): object
    {
        $repository = $this->createMock($interface);
        $this->answerFrom($repository, $interface, $entities);

        return $repository;
    }

    /**
     * @param list<object> $entities
     */
    private function answerFrom(object $repository, string $interface, array $entities): void
    {
        $matching = static fn (array $criteria): array => array_values(array_filter(
            $entities,
            static fn (object $entity): bool => self::matchesCriteria($entity, $criteria),
        ));

        $repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria, ?array $orderBy = null): ?object => $matching($criteria)[0] ?? null
        );
        $repository->method('findBy')->willReturnCallback(
            static fn (array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array => \array_slice($matching($criteria), $offset ?? 0, $limit)
        );

        if (method_exists($interface, 'count')) {
            $repository->method('count')->willReturnCallback(
                static fn (array $criteria = []): int => \count($matching($criteria))
            );
        }
    }

    private static function matchesCriteria(object $entity, array $criteria): bool
    {
        foreach ($criteria as $field => $expected) {
            $actual = $entity->{'get'.ucfirst($field)}();
            if ($actual instanceof AggregateRootId) {
                $actual = $actual->getValue();
            }

            if (\is_array($expected) ? !\in_array($actual, $expected, true) : $actual !== $expected) {
                return false;
            }
        }

        return true;
    }
}
