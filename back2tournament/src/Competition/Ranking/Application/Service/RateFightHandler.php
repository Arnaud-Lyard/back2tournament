<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Service;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Ranking\Application\Model\RateFightCommand;
use App\Competition\Ranking\Domain\Entity\Rating;
use App\Competition\Ranking\Domain\Entity\RatingChangeId;
use App\Competition\Ranking\Domain\Entity\RatingId;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingChangeRepositoryInterface;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class RateFightHandler
{
    private const SETTLED = [ResultStatus::WIN, ResultStatus::LOSS, ResultStatus::DRAW];

    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private RatingRepositoryInterface $ratingRepository;
    private RatingChangeRepositoryInterface $ratingChangeRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        RatingRepositoryInterface $ratingRepository,
        RatingChangeRepositoryInterface $ratingChangeRepository,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->ratingRepository = $ratingRepository;
        $this->ratingChangeRepository = $ratingChangeRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(RateFightCommand $rateFightCommand): void
    {
        $fightId = new FightId($rateFightCommand->getFightId());

        $fight = $this->fightRepository->findOneBy(['id' => $fightId->getValue()]);
        if (!$fight instanceof Fight) {
            throw new NotFoundException('fight not found');
        }

        // A fight counts once.
        if ($this->ratingChangeRepository->count(['fight' => $fightId->getValue()]) > 0) {
            return;
        }

        $one = $fight->getCompetitorOne()->getValue();
        $two = $fight->getCompetitorTwo()->getValue();

        $outcomes = [];
        foreach ($this->resultRepository->findBy(['fight' => $fightId->getValue()]) as $result) {
            $outcomes[$result->getCompetitor()->getValue()] = $result->getStatus();
        }
        if (!\in_array($outcomes[$one] ?? null, self::SETTLED, true) || !\in_array($outcomes[$two] ?? null, self::SETTLED, true)) {
            return;
        }

        // A player profile rates on its 1v1 fights, a clan on the fights of its
        // teams. Two teams of one clan leave its rating as it is.
        $sides = $this->competitorRegistryProvider->rankedAs([$one, $two]);
        if (!isset($sides[$one], $sides[$two])
            || $sides[$one]['type'] !== $sides[$two]['type']
            || $sides[$one]['id'] === $sides[$two]['id']) {
            return;
        }

        $subjectType = RankingSubject::from($sides[$one]['type']);
        $ratingOne = $this->ratingOf($subjectType, $sides[$one]['id'], $fight->getGame());
        $ratingTwo = $this->ratingOf($subjectType, $sides[$two]['id'], $fight->getGame());

        $winner = match (true) {
            ResultStatus::WIN === $outcomes[$one] => $ratingOne,
            ResultStatus::WIN === $outcomes[$two] => $ratingTwo,
            default => null,
        };

        $changes = Rating::settle(
            $ratingOne,
            $ratingTwo,
            $winner,
            $fightId,
            new RatingChangeId(Uuid::v4()->toString()),
            new RatingChangeId(Uuid::v4()->toString()),
        );

        $this->ratingRepository->save($ratingOne);
        $this->ratingRepository->save($ratingTwo);
        foreach ($changes as $change) {
            $this->ratingChangeRepository->save($change);
        }

        foreach ([$ratingOne, $ratingTwo] as $rating) {
            foreach ($rating->pullDomainEvents() as $domainEvent) {
                $this->eventDispatcher->dispatch($domainEvent);
            }
        }
    }

    /**
     * The rating of a player profile or a clan, started on its first fight.
     */
    private function ratingOf(RankingSubject $subjectType, string $subject, GameId $gameId): Rating
    {
        $rating = $this->ratingRepository->findOneBy(['subjectType' => $subjectType, 'subject' => $subject]);

        return $rating instanceof Rating
            ? $rating
            : Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, $subject, $gameId);
    }
}
