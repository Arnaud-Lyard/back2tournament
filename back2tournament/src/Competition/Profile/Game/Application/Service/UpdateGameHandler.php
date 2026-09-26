<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Application\Model\UpdateGameCommand;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class UpdateGameHandler
{
    private GameRepositoryInterface $gameRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;

    public function __construct(
        GameRepositoryInterface $gameRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
    ) {
        $this->gameRepository = $gameRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(UpdateGameCommand $updateGameCommand): string
    {
        $gameId = new GameId($updateGameCommand->getGameId());
        $teamSizes = null === $updateGameCommand->getTeamSizes() ? null : array_map(
            static fn (int $teamSize): TeamSize => new TeamSize($teamSize),
            $updateGameCommand->getTeamSizes(),
        );

        if (!$this->currentUserProvider->isGranted('ROLE_ADMIN')) {
            throw new PermissionDeniedException('the user does not have the necessary permissions');
        }

        $game = $this->gameRepository->findOneBy(['id' => $gameId->getValue()]);
        if (!$game instanceof Game) {
            throw new NotFoundException('game not found');
        }

        Game::update($game, $updateGameCommand->getTitle(), $teamSizes);

        $this->gameRepository->save($game);

        foreach ($game->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $this->serializer->serialize($game, 'json');
    }
}
