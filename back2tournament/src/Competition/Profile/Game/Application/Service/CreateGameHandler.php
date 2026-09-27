<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Service;

use App\Competition\Profile\Game\Application\Model\CreateGameCommand;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Shared\ValueObject\TeamSizeValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class CreateGameHandler
{
    private GameRepositoryInterface $gameRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;

    public function __construct(
        GameRepositoryInterface $gameRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
    ) {
        $this->gameRepository = $gameRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(CreateGameCommand $createGameCommand): string
    {
        $teamSizes = array_map(
            static fn (int $teamSize): TeamSizeValueObject => new TeamSizeValueObject($teamSize),
            $createGameCommand->getTeamSizes(),
        );

        $game = Game::create(
            new GameId(Uuid::v4()->toString()),
            $createGameCommand->getTitle(),
            $teamSizes,
        );

        $this->gameRepository->save($game);

        foreach ($game->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $this->serializer->serialize($game, 'json');
    }
}
