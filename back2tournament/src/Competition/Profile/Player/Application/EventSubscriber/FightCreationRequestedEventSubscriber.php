<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\EventSubscriber;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Event\OnFightCreationRequestedEvent;
use App\Competition\Profile\Player\Application\Event\OnFightPlayersVerifiedEvent;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FightCreationRequestedEventSubscriber implements EventSubscriberInterface
{
    private PlayerRepositoryInterface $playerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->playerRepository = $playerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnFightCreationRequestedEvent::class => 'validatePlayers',
        ];
    }

    public function validatePlayers(OnFightCreationRequestedEvent $event): void
    {
        if ($event->getPlayerOne() === $event->getPlayerTwo()) {
            throw new ValidationException('a player cannot fight itself');
        }

        $playerOne = $this->playerRepository->findOneBy(['id' => $event->getPlayerOne()]);
        if (!$playerOne instanceof Player) {
            throw new NotFoundException('player not found');
        }

        $playerTwo = $this->playerRepository->findOneBy(['id' => $event->getPlayerTwo()]);
        if (!$playerTwo instanceof Player) {
            throw new NotFoundException('player not found');
        }

        // A fight is opened by one of its two sides, never by a bystander.
        $caller = (string) $this->currentUserProvider->getUser()->getId();

        if ($caller !== $playerOne->getUser()->getValue() && $caller !== $playerTwo->getUser()->getValue()) {
            throw new PermissionDeniedException('you do not take part in this fight');
        }

        $this->eventDispatcher->dispatch(new OnFightPlayersVerifiedEvent(
            $event->getPlayerOne(),
            $event->getPlayerTwo(),
        ));
    }
}
