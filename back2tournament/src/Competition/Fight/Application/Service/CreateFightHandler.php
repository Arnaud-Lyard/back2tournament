<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Competition\Fight\Application\Model\CreateFightCommand;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateFightHandler
{
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;
    private RequestStack $requestStack;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        RequestStack $requestStack,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->requestStack = $requestStack;
    }

    public function __invoke(CreateFightCommand $createFightCommand): void
    {
        $fight = Fight::create(
            new FightId(Uuid::v4()->toString()),
            new CompetitorId($createFightCommand->getCompetitorOne()),
            new CompetitorId($createFightCommand->getCompetitorTwo()),
        );

        $this->fightRepository->save($fight);

        $resultOne = Fight::createResult(
            $fight,
            new ResultId(Uuid::v4()->toString()),
            new CompetitorId($createFightCommand->getCompetitorOne()),
        );

        $resultTwo = Fight::createResult(
            $fight,
            new ResultId(Uuid::v4()->toString()),
            new CompetitorId($createFightCommand->getCompetitorTwo()),
        );

        $this->resultRepository->save($resultOne);
        $this->resultRepository->save($resultTwo);

        $this->requestStack->getSession()->set(
            'last_fight_created',
            $this->serializer->serialize($fight, 'json')
        );

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
