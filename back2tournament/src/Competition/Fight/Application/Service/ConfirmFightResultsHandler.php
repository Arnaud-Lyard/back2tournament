<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Competition\Fight\Application\Model\ConfirmFightResultsCommand;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler]
final class ConfirmFightResultsHandler
{
    private ResultRepositoryInterface $resultRepository;
    private FightRepositoryInterface $fightRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;
    private RequestStack $requestStack;

    public function __construct(
        ResultRepositoryInterface $resultRepository,
        FightRepositoryInterface $fightRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        RequestStack $requestStack,
    ) {
        $this->resultRepository = $resultRepository;
        $this->fightRepository = $fightRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->requestStack = $requestStack;
    }

    public function __invoke(ConfirmFightResultsCommand $confirmFightResultsCommand): void
    {
        $fight = $this->fightRepository->findOneBy([
            'id' => $confirmFightResultsCommand->getFight(),
        ]);

        if (!$fight) {
            throw new NotFoundException('Fight not found');
        }

        $existingResultOne = $this->resultRepository->findOneBy([
            'fight' => $confirmFightResultsCommand->getFight(),
            'competitor' => $confirmFightResultsCommand->getCompetitorOne(),
        ]);

        if (!$existingResultOne) {
            throw new NotFoundException('Result not found');
        }

        $existingResultTwo = $this->resultRepository->findOneBy([
            'fight' => $confirmFightResultsCommand->getFight(),
            'competitor' => $confirmFightResultsCommand->getCompetitorTwo(),
        ]);

        if (!$existingResultTwo) {
            throw new NotFoundException('Result not found');
        }

        $declaredBy = $fight->getDeclaredBy();

        if (null === $declaredBy) {
            throw new ConflictException('no outcome has been declared on this fight yet');
        }

        if ($declaredBy->getValue() === $confirmFightResultsCommand->getCompetitorOne()) {
            throw new PermissionDeniedException('the declaring side cannot confirm its own outcome');
        }

        if (ResultStatus::REPORTING !== $existingResultOne->getStatus()
            || ResultStatus::REPORTING !== $existingResultTwo->getStatus()) {
            throw new ConflictException('this fight is not awaiting a confirmation');
        }

        $scoreOne = $existingResultOne->getScore();
        $scoreTwo = $existingResultTwo->getScore();

        $statusOne = match (true) {
            $scoreOne > $scoreTwo => ResultStatus::WIN,
            $scoreOne < $scoreTwo => ResultStatus::LOSS,
            default => ResultStatus::DRAW,
        };

        $statusTwo = match (true) {
            $scoreTwo > $scoreOne => ResultStatus::WIN,
            $scoreTwo < $scoreOne => ResultStatus::LOSS,
            default => ResultStatus::DRAW,
        };

        $resultOne = Fight::confirmResult(
            $fight,
            $existingResultOne,
            $statusOne,
        );

        $resultTwo = Fight::confirmResult(
            $fight,
            $existingResultTwo,
            $statusTwo,
        );

        $this->resultRepository->save($resultOne);
        $this->resultRepository->save($resultTwo);
        $this->fightRepository->save($fight);

        $this->requestStack->getSession()->set(
            'last_fight_result_confirmed',
            $this->serializer->serialize($fight, 'json')
        );

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

    }
}
