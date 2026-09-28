<?php

declare(strict_types=1);

namespace App\Authentication\User\Infrastructure\Repository;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<User> */
final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByUsernameOrEmail(string $identifier): User|null
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :identifier')
            ->orWhere('u.email = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
