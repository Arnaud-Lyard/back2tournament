<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Service;

use App\Authentication\User\Application\Model\RegisterUserCommand;
use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Authentication\User\Domain\Entity\Password;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Entity\Username;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ValidationException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler]
final class RegisterUserHandler
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

    public function __invoke(RegisterUserCommand $registerUserCommand): string
    {
        $email = $registerUserCommand->getEmail();
        $username = $registerUserCommand->getUsername();
        $password = $registerUserCommand->getPassword();
        $locale = new Locale($registerUserCommand->getLocale());

        if ($password !== $registerUserCommand->getPasswordConfirmation()) {
            throw new ValidationException('passwords do not match');
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new ConflictException('email already used');
        }
        if ($this->userRepository->findOneBy(['username' => $username])) {
            throw new ConflictException('username already used');
        }

        $user = User::registerUser(
            new Email($email),
            new Username($username),
            ['ROLE_USER'],
            new Password($password),
            $locale,
        );

        $user->setPassword($this->userPasswordHasher->hash($user, $password));

        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $this->serializer->serialize($user, 'json');
    }
}
