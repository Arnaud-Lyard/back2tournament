<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Application\Model\DisbandTeamCommand;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class DisbandTeamHandler
{
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private ClanRepositoryInterface $clanRepository;
    private PlayerRepositoryInterface $playerRepository;
    private CompetitorRegistryInterface $competitorRegistry;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        ClanRepositoryInterface $clanRepository,
        PlayerRepositoryInterface $playerRepository,
        CompetitorRegistryInterface $competitorRegistry,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->clanRepository = $clanRepository;
        $this->playerRepository = $playerRepository;
        $this->competitorRegistry = $competitorRegistry;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(DisbandTeamCommand $disbandTeamCommand): string
    {
        $teamId = new TeamId($disbandTeamCommand->getTeamId());

        $team = $this->teamRepository->findOneBy(['id' => $teamId->getValue()]);
        if (!$team instanceof Team) {
            throw new NotFoundException('team not found');
        }

        $clan = $this->clanRepository->findOneBy(['id' => $team->getClan()->getValue()]);
        $clanLeader = $clan instanceof Clan ? $this->playerRepository->findOneBy(['id' => $clan->getLeader()->getValue()]) : null;
        if (!$clanLeader instanceof Player
            || $clanLeader->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('only the clan leader disbands its teams');
        }

        if ($this->competitorRegistry->teamHasCompeted($teamId->getValue())) {
            throw new ConflictException('this team has competed and is kept for the record');
        }

        $teamPlayers = $this->teamPlayerRepository->findBy(['team' => $teamId->getValue()]);
        $players = [] === $teamPlayers ? [] : $this->playerRepository->findBy([
            'id' => array_map(static fn (TeamPlayer $teamPlayer): string => $teamPlayer->getPlayer()->getValue(), $teamPlayers),
        ]);
        $disbanded = json_encode($this->normalizeTeam($team, $players), JSON_THROW_ON_ERROR);

        Team::disband($team);

        foreach ($teamPlayers as $teamPlayer) {
            $this->teamPlayerRepository->remove($teamPlayer);
        }
        $this->teamRepository->remove($team);

        foreach ($team->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $disbanded;
    }

    /**
     * The team, its lineup named by battletag, leader first.
     *
     * @param list<Player> $players the lineup
     *
     * @return array<string, mixed>
     */
    private function normalizeTeam(Team $team, array $players): array
    {
        $leader = $team->getLeader()->getValue();
        usort(
            $players,
            static fn (Player $one, Player $two): int => ($two->getId()->getValue() === $leader) <=> ($one->getId()->getValue() === $leader),
        );

        return [
            'id' => ['value' => $team->getId()->getValue()],
            'name' => $team->getName(),
            'clan' => ['value' => $team->getClan()->getValue()],
            'game' => ['value' => $team->getGame()->getValue()],
            'size' => $team->getSize(),
            'leader' => ['value' => $leader],
            'players' => array_map(static fn (Player $player): array => [
                'id' => ['value' => $player->getId()->getValue()],
                'battletag' => $player->getBattletag(),
            ], $players),
            'createdAt' => $team->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $team->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
