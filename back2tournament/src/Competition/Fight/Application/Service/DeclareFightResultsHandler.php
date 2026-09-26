<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\DeclareFightResultsCommand;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\Score;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class DeclareFightResultsHandler
{
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private CompetitorRegistryInterface $competitorRegistry;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        CompetitorRegistryInterface $competitorRegistry,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->competitorRegistry = $competitorRegistry;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(DeclareFightResultsCommand $declareFightResultsCommand): string
    {
        $fightId = new FightId($declareFightResultsCommand->getFightId());
        $score = new Score($declareFightResultsCommand->getScore());
        $opponentScore = new Score($declareFightResultsCommand->getOpponentScore());

        $fight = $this->fightRepository->findOneBy(['id' => $fightId->getValue()]);
        if (!$fight instanceof Fight) {
            throw new NotFoundException('fight not found');
        }

        $represented = $this->competitorRegistry->representedBy((string) $this->currentUserProvider->getUser()->getId());
        $side = $fight->sideAmong($represented);
        if (null === $side) {
            throw new PermissionDeniedException('you do not take part in this fight');
        }
        $opponent = $fight->opponentOf($side);

        $declaring = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $side->getValue()]);
        $opposing = $this->resultRepository->findOneBy(['fight' => $fightId->getValue(), 'competitor' => $opponent->getValue()]);
        if (!$declaring instanceof Result || !$opposing instanceof Result) {
            throw new NotFoundException('result not found');
        }

        Fight::declareOutcome($fight, $side, $declaring, $score, $opposing, $opponentScore);

        $this->resultRepository->save($declaring);
        $this->resultRepository->save($opposing);
        $this->fightRepository->save($fight);

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode(
            FightView::of($fight, [$declaring, $opposing], $this->competitorRegistry->describe([$side->getValue(), $opponent->getValue()]), $represented),
            JSON_THROW_ON_ERROR,
        );
    }
}
