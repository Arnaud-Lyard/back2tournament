<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Service;

use App\Authentication\User\Application\Model\ChangeAvatarCommand;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Repository\UserRepositoryInterface;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Provider\PlayerProfileProviderInterface;
use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\ValueObject\UploadedImageValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class ChangeAvatarHandler
{
    private CurrentUserProviderInterface $currentUserProvider;
    private UserRepositoryInterface $userRepository;
    private ImageProviderInterface $imageProvider;
    private PlayerProfileProviderInterface $playerProfileProvider;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $serializer;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        UserRepositoryInterface $userRepository,
        ImageProviderInterface $imageProvider,
        PlayerProfileProviderInterface $playerProfileProvider,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $serializer,
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->userRepository = $userRepository;
        $this->imageProvider = $imageProvider;
        $this->playerProfileProvider = $playerProfileProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(ChangeAvatarCommand $changeAvatarCommand): string
    {
        $image = null === $changeAvatarCommand->getImage() ? null : new UploadedImageValueObject($changeAvatarCommand->getImage());

        $user = $this->currentUserProvider->getUser();

        $previous = $user->getAvatar();
        if (null !== $image || null !== $previous) {
            User::changeAvatar($user, null === $image ? null : $this->imageProvider->store($image, ImageKind::AVATAR));
            $this->userRepository->save($user);

            foreach ($user->pullDomainEvents() as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
            }

            $this->imageProvider->remove($previous);
        }

        /** @var array<string, mixed> $currentUser */
        $currentUser = $this->serializer->normalize($user);
        $currentUser['players'] = $this->playerProfileProvider->byUser((string) $user->getId());

        return json_encode($currentUser, JSON_THROW_ON_ERROR);
    }
}
