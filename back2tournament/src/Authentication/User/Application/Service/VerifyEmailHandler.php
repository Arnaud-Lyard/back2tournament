<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Service;

use App\Authentication\User\Application\Model\VerifyEmailCommand;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler]
final class VerifyEmailHandler
{
    private UserRepositoryInterface $userRepository;
    private SerializerInterface $serializer;

    public function __construct(UserRepositoryInterface $userRepository, SerializerInterface $serializer)
    {
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    public function __invoke(VerifyEmailCommand $verifyEmailCommand): string
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $verifyEmailCommand->getToken()]);

        if (!$user) {
            throw new ValidationException('invalid verification token');
        }

        $user->verifyEmail();

        $this->userRepository->save($user);

        return $this->serializer->serialize($user, 'json');
    }
}
