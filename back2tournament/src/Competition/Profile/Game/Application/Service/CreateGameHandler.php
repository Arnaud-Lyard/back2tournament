<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\Service;

use App\Competition\Profile\Game\Application\Model\CreateGameCommand;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateGameHandler
{
    private GameRepositoryInterface $gameRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;
    private RequestStack $requestStack;

    public function __construct(
        GameRepositoryInterface $gameRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        RequestStack $requestStack,
    ) {
        $this->gameRepository = $gameRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->requestStack = $requestStack;
    }

    public function __invoke(CreateGameCommand $createGameCommand): void
    {
        $game = Game::create(
            new GameId(Uuid::v4()->toString()),
            $createGameCommand->getTitle(),
        );

        $this->gameRepository->save($game);

        $this->requestStack->getSession()->set(
            'last_game_created',
            $this->serializer->serialize($game, 'json')
        );

        foreach ($game->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
