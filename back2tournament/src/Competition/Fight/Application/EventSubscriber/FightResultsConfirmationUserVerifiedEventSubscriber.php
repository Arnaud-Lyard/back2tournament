<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\EventSubscriber;

use App\Authentication\User\Application\Event\OnFightResultsConfirmationUserVerified;
use App\Competition\Fight\Application\Model\ConfirmFightResultsCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class FightResultsConfirmationUserVerifiedEventSubscriber implements EventSubscriberInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnFightResultsConfirmationUserVerified::class => 'confirmFightResults',
        ];
    }

    public function confirmFightResults(OnFightResultsConfirmationUserVerified $event): void
    {
        $event->setConfirmedFight($this->handle(new ConfirmFightResultsCommand(
            $event->getFight(),
            $event->getUser(),
        )));
    }
}
