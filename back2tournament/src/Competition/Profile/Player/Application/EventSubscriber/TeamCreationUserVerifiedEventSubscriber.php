<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnTeamCreationUserVerifiedEvent;
use App\Competition\Profile\Player\Application\Event\OnPlayersVerifiedEvent;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TeamCreationUserVerifiedEventSubscriber implements EventSubscriberInterface
{
    private PlayerRepositoryInterface $playerRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->playerRepository = $playerRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnTeamCreationUserVerifiedEvent::class => 'validatePlayers',
        ];
    }

    public function validatePlayers(OnTeamCreationUserVerifiedEvent $event): void
    {
        $player = $event->getPlayer();

        if (!$player) {
            throw new ValidationException('a team must have at least one player');
        }

        if (!$this->playerRepository->findOneBy(['id' => $player])) {
            throw new NotFoundException('player not found');
        }

        $this->eventDispatcher->dispatch(new OnPlayersVerifiedEvent(
            $event->getName(),
            $event->getUser(),
            $player,
            $event->getLeader(),
        ));
    }
}
