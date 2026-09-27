<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class FightSchedulerProvider implements FightSchedulerProviderInterface
{
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function schedule(
        string $competitorOne,
        string $competitorTwo,
        string $gameId,
        int $teamSize,
        ?string $tournamentId = null,
    ): Fight {
        $fight = Fight::create(
            new FightId(Uuid::v4()->toString()),
            new CompetitorId($competitorOne),
            new CompetitorId($competitorTwo),
            new GameId($gameId),
            new TeamSize($teamSize),
            null === $tournamentId ? null : new TournamentId($tournamentId),
        );

        $this->fightRepository->save($fight);

        foreach ([$competitorOne, $competitorTwo] as $competitor) {
            $this->resultRepository->save(
                Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), new CompetitorId($competitor))
            );
        }

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $fight;
    }
}
