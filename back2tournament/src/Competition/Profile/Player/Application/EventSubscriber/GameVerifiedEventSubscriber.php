<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\EventSubscriber;

use App\Competition\Profile\Game\Application\Event\OnGameVerifiedEvent;
use App\Competition\Profile\Player\Application\Model\CreatePlayerCommand;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\ConflictException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class GameVerifiedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private PlayerRepositoryInterface $playerRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        PlayerRepositoryInterface $playerRepository
    ) {
        $this->messageBus = $messageBus;
        $this->playerRepository = $playerRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnGameVerifiedEvent::class => 'createPlayer',
        ];
    }

    public function createPlayer(OnGameVerifiedEvent $event): void
    {
        if ($this->playerRepository->findOneBy(['user' => $event->getUser(), 'game' => $event->getGame()])) {
            throw new ConflictException('a player already exists for this user and game');
        }

        $createPlayerCommand = new CreatePlayerCommand();
        $createPlayerCommand->setBattletag($event->getBattletag());
        $createPlayerCommand->setUser($event->getUser());
        $createPlayerCommand->setGame($event->getGame());

        $this->messageBus->dispatch($createPlayerCommand);
    }
}
