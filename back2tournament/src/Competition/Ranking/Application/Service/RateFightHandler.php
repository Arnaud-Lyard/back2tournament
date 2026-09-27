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
use App\Competition\Ranking\Domain\Enum\FightOutcome;
use App\Competition\Ranking\Domain\Enum\RankingSubject;
use App\Competition\Ranking\Domain\Repository\RatingChangeRepositoryInterface;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\TeamSizeValueObject;
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
        $clans = [];
        foreach ($this->resultRepository->findBy(['fight' => $fightId->getValue()]) as $result) {
            $outcomes[$result->getCompetitor()->getValue()] = $result->getStatus();
            $clans[$result->getCompetitor()->getValue()] = $result->getClan()?->getValue();
        }
        if (!\in_array($outcomes[$one] ?? null, self::SETTLED, true) || !\in_array($outcomes[$two] ?? null, self::SETTLED, true)) {
            return;
        }

        $lineups = $this->competitorRegistryProvider->lineups([$one, $two]);
        if (!isset($lineups[$one], $lineups[$two])) {
            return;
        }

        $outcome = match (true) {
            ResultStatus::WIN === $outcomes[$one] => FightOutcome::SIDE_ONE_WON,
            ResultStatus::WIN === $outcomes[$two] => FightOutcome::SIDE_TWO_WON,
            default => FightOutcome::DRAW,
        };

        // Every format is a ranking of its own: a 2v2 counts in the 2v2 rankings.
        $gameId = $fight->getGame();
        $teamSize = new TeamSizeValueObject($fight->getTeamSize());

        $sides = [];

        // Every player profile of a side moves, by what the fight was worth
        // to its side. Nobody stands on both sides: fights refuse it.
        $playersOne = $lineups[$one]['players'];
        $playersTwo = $lineups[$two]['players'];
        if ([] !== $playersOne && [] !== $playersTwo && [] === array_intersect($playersOne, $playersTwo)) {
            $sides[] = [
                array_map(fn (string $player): Rating => $this->ratingOf(RankingSubject::PLAYER, $player, $gameId, $teamSize), $playersOne),
                array_map(fn (string $player): Rating => $this->ratingOf(RankingSubject::PLAYER, $player, $gameId, $teamSize), $playersTwo),
            ];
        }

        // A clan rates on the fights of its teams, and on the duels of its
        // members, against another clan: two sides of one clan leave it as it
        // is. Each side counts for the clan its result recorded when the fight
        // opened, so that counting the fight again gives the same ranking.
        $clanOne = $clans[$one] ?? null;
        $clanTwo = $clans[$two] ?? null;
        if (null !== $clanOne && null !== $clanTwo && $clanOne !== $clanTwo) {
            $sides[] = [
                [$this->ratingOf(RankingSubject::CLAN, $clanOne, $gameId, $teamSize)],
                [$this->ratingOf(RankingSubject::CLAN, $clanTwo, $gameId, $teamSize)],
            ];
        }

        foreach ($sides as [$sideOne, $sideTwo]) {
            $ratings = [...$sideOne, ...$sideTwo];
            $changes = Rating::settle(
                $sideOne,
                $sideTwo,
                $outcome,
                $fightId,
                array_map(static fn (): RatingChangeId => new RatingChangeId(Uuid::v4()->toString()), $ratings),
            );

            foreach ($ratings as $rating) {
                $this->ratingRepository->save($rating);
            }
            foreach ($changes as $change) {
                $this->ratingChangeRepository->save($change);
            }

            foreach ($ratings as $rating) {
                foreach ($rating->pullDomainEvents() as $domainEvent) {
                    $this->eventDispatcher->dispatch($domainEvent);
                }
            }
        }
    }

    /**
     * The rating of a player profile or a clan in one format, started on its
     * first fight in that format.
     */
    private function ratingOf(RankingSubject $subjectType, string $subject, GameId $gameId, TeamSizeValueObject $teamSize): Rating
    {
        $rating = $this->ratingRepository->findOneBy([
            'subjectType' => $subjectType,
            'subject' => $subject,
            'teamSize' => $teamSize->getValue(),
        ]);

        return $rating instanceof Rating
            ? $rating
            : Rating::start(new RatingId(Uuid::v4()->toString()), $subjectType, $subject, $gameId, $teamSize);
    }
}
