<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Application\Model\ChangeGameImageCommand;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Media\Image\Domain\Enum\ImageKind;
use App\Media\Shared\Domain\Provider\ImageProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\ValueObject\UploadedImageValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class ChangeGameImageHandler
{
    private GameRepositoryInterface $gameRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private ImageProviderInterface $imageProvider;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;

    public function __construct(
        GameRepositoryInterface $gameRepository,
        CurrentUserProviderInterface $currentUserProvider,
        ImageProviderInterface $imageProvider,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
    ) {
        $this->gameRepository = $gameRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->imageProvider = $imageProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(ChangeGameImageCommand $changeGameImageCommand): string
    {
        $gameId = new GameId($changeGameImageCommand->getGameId());
        $image = null === $changeGameImageCommand->getImage() ? null : new UploadedImageValueObject($changeGameImageCommand->getImage());

        if (!$this->currentUserProvider->isGranted('ROLE_ADMIN')) {
            throw new PermissionDeniedException('only an administrator illustrates a game');
        }

        $game = $this->gameRepository->findOneBy(['id' => $gameId->getValue()]);
        if (!$game instanceof Game) {
            throw new NotFoundException('game not found');
        }

        $previous = $game->getImage();
        if (null !== $image || null !== $previous) {
            Game::illustrate($game, null === $image ? null : $this->imageProvider->store($image, ImageKind::GAME));
            $this->gameRepository->save($game);

            foreach ($game->pullDomainEvents() as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
            }

            // The former picture goes once the game no longer points to it.
            $this->imageProvider->remove($previous);
        }

        return $this->serializer->serialize($game, 'json');
    }
}
