<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\EventSubscriber;

use App\Competition\Competitor\Application\Event\OnCompetitorsReadyEvent;
use App\Competition\Competitor\Application\Model\CreateCompetitorCommand;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Profile\Player\Application\Event\OnFightPlayersVerifiedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;;

final class OnFightPlayersVerifiedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private EventDispatcherInterface $eventDispatcher;
    private CompetitorRepositoryInterface $competitorRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        EventDispatcherInterface $eventDispatcher,
        CompetitorRepositoryInterface $competitorRepository
    ) {
        $this->messageBus = $messageBus;
        $this->eventDispatcher = $eventDispatcher;
        $this->competitorRepository = $competitorRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OnFightPlayersVerifiedEvent::class => 'createCompetitor',
        ];
    }

    public function createCompetitor(OnFightPlayersVerifiedEvent $event): void
    {
        $competitorOne = $this->competitorRepository->findOneBy([
            'reference' => $event->getPlayerOne(),
            'type' => CompetitorType::PLAYER,
        ]);
        $competitorTwo = $this->competitorRepository->findOneBy([
            'reference' => $event->getPlayerTwo(),
            'type' => CompetitorType::PLAYER,
        ]);

        if (!$competitorOne) {
            $createPlayerOneCompetitorCommand = new CreateCompetitorCommand();
            $createPlayerOneCompetitorCommand->setType(CompetitorType::PLAYER);
            $createPlayerOneCompetitorCommand->setReference($event->getPlayerOne());
            $this->messageBus->dispatch($createPlayerOneCompetitorCommand);

            $competitorOne = $this->competitorRepository->findOneBy([
                'reference' => $event->getPlayerOne(),
                'type' => CompetitorType::PLAYER,
            ]);
        }

        if (!$competitorTwo) {
            $createPlayerTwoCompetitorCommand = new CreateCompetitorCommand();
            $createPlayerTwoCompetitorCommand->setType(CompetitorType::PLAYER);
            $createPlayerTwoCompetitorCommand->setReference($event->getPlayerTwo());
            $this->messageBus->dispatch($createPlayerTwoCompetitorCommand);

            $competitorTwo = $this->competitorRepository->findOneBy([
                'reference' => $event->getPlayerTwo(),
                'type' => CompetitorType::PLAYER,
            ]);
        }

        $this->eventDispatcher->dispatch(new OnCompetitorsReadyEvent(
            $competitorOne->getId()->getValue(),
            $competitorTwo->getId()->getValue(),
        ));
    }
}
