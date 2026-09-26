<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Competition\Fight\Application\Model\UpdateFightResultsCommand;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Score;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\DeclaredStatusValueObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler]
final class UpdateFightResultsHandler
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

    public function __invoke(UpdateFightResultsCommand $updateFightResultsCommand): void
    {
        $declaringCompetitor = new CompetitorId($updateFightResultsCommand->getCompetitorOne());
        $opposingCompetitor = new CompetitorId($updateFightResultsCommand->getCompetitorTwo());
        $declaringStatus = ResultStatus::from(
            new DeclaredStatusValueObject($updateFightResultsCommand->getCompetitorOneStatus())->getValue()
        );
        $opposingStatus = ResultStatus::from(
            new DeclaredStatusValueObject($updateFightResultsCommand->getCompetitorTwoStatus())->getValue()
        );

        $fight = $this->fightRepository->findOneBy([
            'id' => $updateFightResultsCommand->getFight(),
        ]);

        if (!$fight) {
            throw new NotFoundException('Fight not found');
        }

        $existingResultOne = $this->resultRepository->findOneBy([
            'fight' => $updateFightResultsCommand->getFight(),
            'competitor' => $updateFightResultsCommand->getCompetitorOne(),
        ]);

        if (!$existingResultOne) {
            throw new NotFoundException('Result not found');
        }

        $existingResultTwo = $this->resultRepository->findOneBy([
            'fight' => $updateFightResultsCommand->getFight(),
            'competitor' => $updateFightResultsCommand->getCompetitorTwo(),
        ]);

        if (!$existingResultTwo) {
            throw new NotFoundException('Result not found');
        }

        $resultOne = Fight::updateResult(
            $fight,
            $existingResultOne,
            new Score($updateFightResultsCommand->getCompetitorOneScore())->getValue(),
            $declaringCompetitor,
            $declaringStatus,
        );

        $resultTwo = Fight::updateResult(
            $fight,
            $existingResultTwo,
            new Score($updateFightResultsCommand->getCompetitorTwoScore())->getValue(),
            $opposingCompetitor,
            $opposingStatus,
        );

        $fight->setDeclaredBy($declaringCompetitor);

        $this->resultRepository->save($resultOne);
        $this->resultRepository->save($resultTwo);
        $this->fightRepository->save($fight);

        $this->requestStack->getSession()->set(
            'last_fight_result_updated',
            $this->serializer->serialize($fight, 'json')
        );

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

    }
}
