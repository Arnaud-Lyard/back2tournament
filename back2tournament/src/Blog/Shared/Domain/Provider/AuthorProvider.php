<?php

declare(strict_types=1);

namespace App\Blog\Shared\Domain\Provider;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;

final class AuthorProvider implements AuthorProviderInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function usernames(array $userIds): array
    {
        $userIds = array_values(array_unique($userIds));
        if ([] === $userIds) {
            return [];
        }

        $usernames = [];
        foreach ($this->userRepository->findBy(['id' => $userIds]) as $user) {
            if ($user instanceof User && null !== $user->getId()) {
                $usernames[$user->getId()] = (string) $user->getUsername();
            }
        }

        return $usernames;
    }
}
