<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Player\Application\Model\DeletePlayerCommand;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class DeletePlayerHandler
{
    private PlayerRepositoryInterface $playerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private CompetitorIdProviderInterface $competitorIdProvider;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        CompetitorIdProviderInterface $competitorIdProvider,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
    ) {
        $this->playerRepository = $playerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->competitorIdProvider = $competitorIdProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(DeletePlayerCommand $deletePlayerCommand): string
    {
        $playerId = new PlayerId($deletePlayerCommand->getPlayerId());

        $player = $this->playerRepository->findOneBy(['id' => $playerId->getValue()]);
        if (!$player instanceof Player) {
            throw new NotFoundException('player profile not found');
        }

        if ($player->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('this player profile belongs to another account');
        }

        if ($this->competitorIdProvider->takesPartInFights($playerId->getValue())) {
            throw new ConflictException('this player profile takes part in fights and cannot be deleted');
        }

        Player::delete($player);

        $deletedPlayer = $this->serializer->serialize($player, 'json');

        $this->playerRepository->remove($player);

        foreach ($player->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $deletedPlayer;
    }
}
