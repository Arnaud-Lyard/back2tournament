<?php

declare(strict_types=1);

namespace App\Competition\Profile\Game\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnPlayerCreationAdminVerifiedEvent;
use App\Competition\Profile\Game\Application\Event\OnGameVerifiedEvent;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PlayerCreationAdminVerifiedEventSubscriber implements EventSubscriberInterface
{
    private GameRepositoryInterface $gameRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        GameRepositoryInterface $gameRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->gameRepository = $gameRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnPlayerCreationAdminVerifiedEvent::class => 'validateGame',
        ];
    }

    public function validateGame(OnPlayerCreationAdminVerifiedEvent $event): void
    {
        $game = $this->gameRepository->findOneBy(['id' => $event->getGame()]);
        if (!$game) {
            throw new NotFoundException('game not found');
        }

        $this->eventDispatcher->dispatch(new OnGameVerifiedEvent(
            $event->getBattletag(),
            $event->getUser(),
            $event->getGame(),
        ));
    }
}
