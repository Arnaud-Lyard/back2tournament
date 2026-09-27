<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Shared\ValueObject\TeamSizeValueObject;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class FightSchedulerProvider implements FightSchedulerProviderInterface
{
    private FightRepositoryInterface $fightRepository;
    private ResultRepositoryInterface $resultRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FightRepositoryInterface $fightRepository,
        ResultRepositoryInterface $resultRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->fightRepository = $fightRepository;
        $this->resultRepository = $resultRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
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
            new TeamSizeValueObject($teamSize),
            null === $tournamentId ? null : new TournamentId($tournamentId),
        );

        $this->fightRepository->save($fight);

        // Each side's result keeps the clan it plays for today: the fight
        // stays with that clan whatever its players do next.
        $lineups = $this->competitorRegistryProvider->lineups([$competitorOne, $competitorTwo]);

        foreach ([$competitorOne, $competitorTwo] as $competitor) {
            $clan = $lineups[$competitor]['clan'] ?? null;

            $this->resultRepository->save(Fight::createResult(
                $fight,
                new ResultId(Uuid::v4()->toString()),
                new CompetitorId($competitor),
                null === $clan ? null : new ClanId($clan),
            ));
        }

        foreach ($fight->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $fight;
    }
}
