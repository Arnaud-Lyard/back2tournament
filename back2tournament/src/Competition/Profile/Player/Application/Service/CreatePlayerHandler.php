<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Service;

use App\Competition\Profile\Player\Application\Model\CreatePlayerCommand;
use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreatePlayerHandler
{
    private PlayerRepositoryInterface $playerRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;
    private RequestStack $requestStack;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        RequestStack $requestStack,
    ) {
        $this->playerRepository = $playerRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->requestStack = $requestStack;
    }

    public function __invoke(CreatePlayerCommand $createPlayerCommand): void
    {
        $player = Player::create(
            new PlayerId(Uuid::v4()->toString()),
            $createPlayerCommand->getBattletag(),
            new GameId($createPlayerCommand->getGame()),
            new UserId($createPlayerCommand->getUser())
        );

        $this->playerRepository->save($player);

        $this->requestStack->getSession()->set(
            'last_player_created',
            $this->serializer->serialize($player, 'json')
        );

        foreach ($player->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
