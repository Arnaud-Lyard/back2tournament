<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Service;

use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Authentication\User\Domain\Entity\Password;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Entity\Username;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Shared\Exception\ConflictException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class CreateUserService
{
    private UserRepositoryInterface $userRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;
    private PasswordHasherInterface $userPasswordHasher;

    public function __construct(
        UserRepositoryInterface $userRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        PasswordHasherInterface $userPasswordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function handle(string $email, string $username, array $roles, string $password): string
    {
        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new ConflictException('email already used');
        }
        if ($this->userRepository->findOneBy(['username' => $username])) {
            throw new ConflictException('username already used');
        }

        $user = User::registerUser(
          new Email($email),
          new Username($username),
          $roles,
          new Password($password),
          new Locale('fr'),
        );

        $user->setPassword($this->userPasswordHasher->hash($user, $password));

        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $this->serializer->serialize($user, 'json');
    }
}
