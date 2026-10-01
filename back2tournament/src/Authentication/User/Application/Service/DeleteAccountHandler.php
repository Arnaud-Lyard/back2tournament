<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Service;

use App\Authentication\User\Application\Model\DeleteAccountCommand;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Competition\Shared\Domain\Provider\AccountErasureProviderInterface;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class DeleteAccountHandler
{
    private CurrentUserProviderInterface $currentUserProvider;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherInterface $passwordHasher;
    private AccountErasureProviderInterface $accountErasureProvider;
    private ImageProviderInterface $imageProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        UserRepositoryInterface $userRepository,
        PasswordHasherInterface $passwordHasher,
        AccountErasureProviderInterface $accountErasureProvider,
        ImageProviderInterface $imageProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->accountErasureProvider = $accountErasureProvider;
        $this->imageProvider = $imageProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(DeleteAccountCommand $deleteAccountCommand): string
    {
        $user = $this->currentUserProvider->getUser();

        $this->currentUserProvider->confirmPassword($deleteAccountCommand->getPassword());
        $this->accountErasureProvider->ensureErasable((string) $user->getId());

        $avatar = $user->getAvatar();

        User::erase($user, $this->passwordHasher->hash($user, bin2hex(random_bytes(32))));
        $this->userRepository->save($user);

        foreach ($user->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        $this->imageProvider->remove($avatar);

        return json_encode(['deleted' => true], JSON_THROW_ON_ERROR);
    }
}
