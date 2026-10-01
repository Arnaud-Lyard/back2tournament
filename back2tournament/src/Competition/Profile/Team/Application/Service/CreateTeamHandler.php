<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Service;

use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Application\Model\CreateTeamCommand;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayerId;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamNameValueObject;
use App\Shared\ValueObject\TeamSizeValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class CreateTeamHandler
{
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private GameRepositoryInterface $gameRepository;
    private PlayerRepositoryInterface $playerRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        GameRepositoryInterface $gameRepository,
        PlayerRepositoryInterface $playerRepository,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->gameRepository = $gameRepository;
        $this->playerRepository = $playerRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(CreateTeamCommand $createTeamCommand): string
    {
        $clanId = new ClanId($createTeamCommand->getClan());
        $name = new TeamNameValueObject($createTeamCommand->getName());
        $size = new TeamSizeValueObject($createTeamCommand->getSize());
        $leader = new PlayerId($createTeamCommand->getLeader());
        $lineup = array_map(static fn (string $player): PlayerId => new PlayerId($player), $createTeamCommand->getPlayers());
        $lineupIds = array_values(array_unique(array_map(static fn (PlayerId $player): string => $player->getValue(), $lineup)));

        $clan = $this->clanRepository->findOneBy(['id' => $clanId->getValue(), 'dissolvedAt' => null]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        $clanLeader = $this->playerRepository->findOneBy(['id' => $clan->getLeader()->getValue()]);
        if (!$clanLeader instanceof Player
            || $clanLeader->getUser()->getValue() !== $createTeamCommand->getUser()) {
            throw new PermissionDeniedException('only the clan leader composes its teams');
        }

        $game = $this->gameRepository->findOneBy(['id' => $clan->getGame()->getValue()]);
        if (!$game instanceof Game) {
            throw new NotFoundException('game not found');
        }

        if (!$game->supportsTeamSize($size->getValue())) {
            throw new ValidationException(\sprintf('%s is not played %2$dv%2$d', $game->getTitle(), $size->getValue()));
        }

        $members = $this->clanMemberRepository->findBy([
            'clan' => $clanId->getValue(),
            'player' => $lineupIds,
            'status' => ClanMemberStatus::ACTIVE,
        ]);
        if (\count($members) !== \count($lineupIds)) {
            throw new ValidationException('every player of the lineup must be an active member of the clan');
        }

        $team = Team::create(
            new TeamId(Uuid::v4()->toString()),
            $name,
            $clanId,
            $clan->getGame(),
            $size,
            $leader,
            $lineup,
        );

        $this->teamRepository->save($team);

        foreach ($lineup as $player) {
            $this->teamPlayerRepository->save(
                Team::createTeamPlayer($team, new TeamPlayerId(Uuid::v4()->toString()), $player)
            );
        }

        foreach ($team->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode(
            $this->normalizeTeam($team, $this->playerRepository->findBy(['id' => $lineupIds])),
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param list<Player> $players
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
