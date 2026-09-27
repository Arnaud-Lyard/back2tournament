<?php

declare(strict_types=1);

namespace App\Competition\Shared\Domain\Provider;

use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CompetitorRegistryProvider implements CompetitorRegistryProviderInterface
{
    /**
     * How many player profiles, and how many teams, a name search keeps.
     */
    private const NAMED_AT_MOST = 100;

    private CompetitorRepositoryInterface $competitorRepository;
    private PlayerRepositoryInterface $playerRepository;
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private ClanTagProviderInterface $clanTagProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CompetitorRepositoryInterface $competitorRepository,
        PlayerRepositoryInterface $playerRepository,
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        ClanTagProviderInterface $clanTagProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->competitorRepository = $competitorRepository;
        $this->playerRepository = $playerRepository;
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->clanTagProvider = $clanTagProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function enlistPlayer(string $playerId): string
    {
        return $this->enlist(CompetitorType::PLAYER, $playerId);
    }

    public function enlistTeam(string $teamId): string
    {
        return $this->enlist(CompetitorType::TEAM, $teamId);
    }

    public function representedBy(string $userId): array
    {
        $playerIds = [];
        foreach ($this->playerRepository->findBy(['user' => $userId]) as $player) {
            $playerIds[] = $player->getId()->getValue();
        }

        if ([] === $playerIds) {
            return [];
        }

        $teamIds = [];
        foreach ($this->teamRepository->findBy(['leader' => $playerIds]) as $team) {
            $teamIds[] = $team->getId()->getValue();
        }

        $competitors = $this->competitorRepository->findBy(['type' => CompetitorType::PLAYER, 'reference' => $playerIds]);
        if ([] !== $teamIds) {
            $competitors = array_merge(
                $competitors,
                $this->competitorRepository->findBy(['type' => CompetitorType::TEAM, 'reference' => $teamIds]),
            );
        }

        return array_map(static fn (Competitor $competitor): string => $competitor->getId()->getValue(), $competitors);
    }

    public function competitorsOfPlayer(string $playerId): array
    {
        $teamIds = [];
        foreach ($this->teamPlayerRepository->findBy(['player' => $playerId]) as $teamPlayer) {
            $teamIds[] = $teamPlayer->getTeam()->getValue();
        }

        $competitors = $this->competitorRepository->findBy(['type' => CompetitorType::PLAYER, 'reference' => $playerId]);
        if ([] !== $teamIds) {
            $competitors = array_merge(
                $competitors,
                $this->competitorRepository->findBy(['type' => CompetitorType::TEAM, 'reference' => $teamIds]),
            );
        }

        return array_map(static fn (Competitor $competitor): string => $competitor->getId()->getValue(), $competitors);
    }

    public function describe(array $competitorIds): array
    {
        $competitorIds = array_values(array_unique($competitorIds));
        if ([] === $competitorIds) {
            return [];
        }

        $competitors = $this->competitorRepository->findBy(['id' => $competitorIds]);

        $references = [CompetitorType::PLAYER->value => [], CompetitorType::TEAM->value => []];
        foreach ($competitors as $competitor) {
            $references[$competitor->getType()->value][] = $competitor->getReference();
        }

        $names = [];
        $tags = [];
        if ([] !== $references[CompetitorType::PLAYER->value]) {
            foreach ($this->playerRepository->findBy(['id' => $references[CompetitorType::PLAYER->value]]) as $player) {
                $names[$player->getId()->getValue()] = $player->getBattletag();
            }
            foreach ($this->clanTagProvider->clansOfPlayers($references[CompetitorType::PLAYER->value]) as $playerId => $clan) {
                $tags[$playerId] = $clan['tag'];
            }
        }
        if ([] !== $references[CompetitorType::TEAM->value]) {
            $clanOfTeam = [];
            foreach ($this->teamRepository->findBy(['id' => $references[CompetitorType::TEAM->value]]) as $team) {
                $names[$team->getId()->getValue()] = $team->getName();
                $clanOfTeam[$team->getId()->getValue()] = $team->getClan()->getValue();
            }
            $clanTags = $this->clanTagProvider->tagsOfClans(array_values($clanOfTeam));
            foreach ($clanOfTeam as $teamId => $clanId) {
                $tags[$teamId] = $clanTags[$clanId] ?? null;
            }
        }

        $described = [];
        foreach ($competitors as $competitor) {
            $described[$competitor->getId()->getValue()] = [
                'type' => $competitor->getType()->value,
                'reference' => $competitor->getReference(),
                'name' => $names[$competitor->getReference()] ?? null,
                'tag' => $tags[$competitor->getReference()] ?? null,
            ];
        }

        return $described;
    }

    public function lineups(array $competitorIds): array
    {
        $competitorIds = array_values(array_unique($competitorIds));
        if ([] === $competitorIds) {
            return [];
        }

        $competitors = $this->competitorRepository->findBy(['id' => $competitorIds]);

        $references = [CompetitorType::PLAYER->value => [], CompetitorType::TEAM->value => []];
        foreach ($competitors as $competitor) {
            $references[$competitor->getType()->value][] = $competitor->getReference();
        }

        // A player profile plays for the clan it is an active member of.
        $clansOfPlayers = [] === $references[CompetitorType::PLAYER->value]
            ? []
            : $this->clanTagProvider->clansOfPlayers($references[CompetitorType::PLAYER->value]);

        // A team plays for its clan, with its lineup.
        $teams = [];
        if ([] !== $references[CompetitorType::TEAM->value]) {
            foreach ($this->teamRepository->findBy(['id' => $references[CompetitorType::TEAM->value]]) as $team) {
                $teams[$team->getId()->getValue()] = ['players' => [], 'clan' => $team->getClan()->getValue()];
            }
            $lineup = [] === $teams ? [] : $this->teamPlayerRepository->findBy(['team' => array_keys($teams)]);
            foreach ($lineup as $teamPlayer) {
                $teams[$teamPlayer->getTeam()->getValue()]['players'][] = $teamPlayer->getPlayer()->getValue();
            }
        }

        $lineups = [];
        foreach ($competitors as $competitor) {
            $reference = $competitor->getReference();

            if (CompetitorType::PLAYER === $competitor->getType()) {
                $lineups[$competitor->getId()->getValue()] = [
                    'players' => [$reference],
                    'clan' => $clansOfPlayers[$reference]['id'] ?? null,
                ];
            } elseif (isset($teams[$reference])) {
                $lineups[$competitor->getId()->getValue()] = $teams[$reference];
            }
        }

        return $lineups;
    }

    public function named(string $search, ?string $gameId = null): array
    {
        $playerIds = array_map(
            static fn ($player): string => $player->getId()->getValue(),
            $this->playerRepository->findNamed($search, $gameId, self::NAMED_AT_MOST),
        );
        $teamIds = array_map(
            static fn ($team): string => $team->getId()->getValue(),
            $this->teamRepository->findNamed($search, $gameId, self::NAMED_AT_MOST),
        );

        $competitors = [] === $playerIds ? [] : $this->competitorRepository->findBy(['type' => CompetitorType::PLAYER, 'reference' => $playerIds]);
        if ([] !== $teamIds) {
            $competitors = array_merge(
                $competitors,
                $this->competitorRepository->findBy(['type' => CompetitorType::TEAM, 'reference' => $teamIds]),
            );
        }

        return array_map(static fn (Competitor $competitor): string => $competitor->getId()->getValue(), $competitors);
    }

    public function teamHasCompeted(string $teamId): bool
    {
        return null !== $this->competitorRepository->findOneBy(['type' => CompetitorType::TEAM, 'reference' => $teamId]);
    }

    private function enlist(CompetitorType $type, string $reference): string
    {
        $competitor = $this->competitorRepository->findOneBy(['type' => $type, 'reference' => $reference]);
        if ($competitor instanceof Competitor) {
            return $competitor->getId()->getValue();
        }

        $competitor = Competitor::create(new CompetitorId(Uuid::v4()->toString()), $type, $reference);

        $this->competitorRepository->save($competitor);

        foreach ($competitor->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $competitor->getId()->getValue();
    }
}
