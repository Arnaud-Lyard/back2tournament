<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Player\Application\Model\UpdatePlayerCommand;
use App\Competition\Profile\Player\Domain\Entity\Battletag;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class UpdatePlayerHandler
{
    private PlayerRepositoryInterface $playerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
    ) {
        $this->playerRepository = $playerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(UpdatePlayerCommand $updatePlayerCommand): string
    {
        $playerId = new PlayerId($updatePlayerCommand->getPlayerId());
        $battletag = new Battletag($updatePlayerCommand->getBattletag());

        $player = $this->playerRepository->findOneBy(['id' => $playerId->getValue()]);
        if (!$player instanceof Player) {
            throw new NotFoundException('player profile not found');
        }

        if ($player->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('this player profile belongs to another account');
        }

        Player::update($player, $battletag->getValue());

        $this->playerRepository->save($player);

        foreach ($player->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $this->serializer->serialize($player, 'json');
    }
}
