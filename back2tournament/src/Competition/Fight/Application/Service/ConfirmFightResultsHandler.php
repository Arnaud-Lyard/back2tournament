<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\ConfirmFightResultsCommand;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class ConfirmFightResultsHandler
{
    private ResultRepositoryInterface $resultRepository;
    private FightRepositoryInterface $fightRepository;
    private CompetitorRegistryInterface $competitorRegistry;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ResultRepositoryInterface $resultRepository,
        FightRepositoryInterface $fightRepository,
        CompetitorRegistryInterface $competitorRegistry,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->resultRepository = $resultRepository;
        $this->fightRepository = $fightRepository;
        $this->competitorRegistry = $competitorRegistry;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(ConfirmFightResultsCommand $confirmFightResultsCommand): string
    {
        $fightId = new FightId($confirmFightResultsCommand->getFightId());

        $fight = $this->fightRepository->findOneBy(['id' => $fightId->getValue()]);
        if (!$fight instanceof Fight) {
            throw new NotFoundException('Fight not found');
        }

        $represented = $this->competitorRegistry->representedBy((string) $this->currentUserProvider->getUser()->getId());
        $side = $fight->sideAmong($represented);
        if (null === $side) {
            throw new PermissionDeniedException('you do not take part in this fight');
        }

        $resultOne = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $fight->getCompetitorOne()->getValue()]);
        $resultTwo = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $fight->getCompetitorTwo()->getValue()]);
        if (!$resultOne instanceof Result || !$resultTwo instanceof Result) {
            throw new NotFoundException('Result not found');
        }

        Fight::confirmOutcome($fight, $side, $resultOne, $resultTwo);

        $this->resultRepository->save($resultOne);
        $this->resultRepository->save($resultTwo);
        $this->fightRepository->save($fight);

        // FightSettledEvent: a tournament bracket moves its winner on from here.
        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode(
            FightView::of(
                $fight,
                [$resultOne, $resultTwo],
                $this->competitorRegistry->describe([$fight->getCompetitorOne()->getValue(), $fight->getCompetitorTwo()->getValue()]),
                $represented,
            ),
            JSON_THROW_ON_ERROR,
        );
    }
}
