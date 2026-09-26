<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Tournament\Application\Model\RegisterParticipantCommand;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\ParticipantId;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Registers a player profile in a 1v1 tournament, or a team in an NvN one. The
 * caller owns the profile, or leads the team.
 */
#[AsMessageHandler]
final class RegisterParticipantHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private ParticipantRepositoryInterface $participantRepository;
    private PlayerRepositoryInterface $playerRepository;
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private CompetitorRegistryInterface $competitorRegistry;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        ParticipantRepositoryInterface $participantRepository,
        PlayerRepositoryInterface $playerRepository,
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        CompetitorRegistryInterface $competitorRegistry,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->participantRepository = $participantRepository;
        $this->playerRepository = $playerRepository;
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->competitorRegistry = $competitorRegistry;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(RegisterParticipantCommand $registerParticipantCommand): string
    {
        $tournamentId = new TournamentId($registerParticipantCommand->getTournamentId());

        $tournament = $this->tournamentRepository->findOneBy(['id' => $tournamentId->getValue()]);
        if (!$tournament instanceof Tournament) {
            throw new NotFoundException('tournament not found');
        }

        $registered = $this->participantRepository->findBy(['tournament' => $tournamentId->getValue()]);
        Tournament::ensureOpenForRegistration($tournament, \count($registered));

        $competitorId = 1 === $tournament->getTeamSize()
            ? $this->enlistPlayer($tournament, $registerParticipantCommand->getPlayerId())
            : $this->enlistTeam($tournament, $registerParticipantCommand->getTeamId(), $registered);

        foreach ($registered as $participant) {
            if ($participant->getCompetitor()->getValue() === $competitorId) {
                throw new ConflictException('already registered in this tournament');
            }
        }

        $participant = Tournament::register(
            $tournament,
            new ParticipantId(Uuid::v4()->toString()),
            new CompetitorId($competitorId),
            \count($registered),
        );

        $this->participantRepository->save($participant);
        $this->tournamentRepository->save($tournament);

        foreach ($tournament->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode(
            $this->normalizeParticipant($participant, $this->competitorRegistry->describe([$competitorId])),
            JSON_THROW_ON_ERROR,
        );
    }

    private function enlistPlayer(Tournament $tournament, ?string $player): string
    {
        if (null === $player) {
            throw new ValidationException('this tournament is played 1v1: register a player profile');
        }

        $playerId = new PlayerId($player);

        $profile = $this->playerRepository->findOneBy(['id' => $playerId->getValue()]);
        if (!$profile instanceof Player) {
            throw new NotFoundException('player not found');
        }

        if ($profile->getGame()->getValue() !== $tournament->getGame()->getValue()) {
            throw new ValidationException('this player plays another game than the tournament');
        }

        if ($profile->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('you register only your own player profile');
        }

        return $this->competitorRegistry->enlistPlayer($playerId->getValue());
    }

    /**
     * @param list<Participant> $registered
     */
    private function enlistTeam(Tournament $tournament, ?string $team, array $registered): string
    {
        if (null === $team) {
            throw new ValidationException(\sprintf('this tournament is played %1$dv%1$d: register a team', $tournament->getTeamSize()));
        }

        $teamId = new TeamId($team);

        $lineup = $this->teamRepository->findOneBy(['id' => $teamId->getValue()]);
        if (!$lineup instanceof Team) {
            throw new NotFoundException('team not found');
        }

        if ($lineup->getGame()->getValue() !== $tournament->getGame()->getValue() || $lineup->getSize() !== $tournament->getTeamSize()) {
            throw new ValidationException(\sprintf('this team does not play this game %1$dv%1$d', $tournament->getTeamSize()));
        }

        $leader = $this->playerRepository->findOneBy(['id' => $lineup->getLeader()->getValue()]);
        if (!$leader instanceof Player || $leader->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('only the team leader registers the team');
        }

        // A player plays for one team per tournament.
        $registeredTeams = [];
        $described = $this->competitorRegistry->describe(array_map(
            static fn (Participant $participant): string => $participant->getCompetitor()->getValue(),
            $registered,
        ));
        foreach ($described as $description) {
            if (CompetitorType::TEAM->value === $description['type'] && $description['reference'] !== $teamId->getValue()) {
                $registeredTeams[] = $description['reference'];
            }
        }

        if ([] !== $registeredTeams) {
            $teamPlayers = $this->teamPlayerRepository->findBy(['team' => array_merge([$teamId->getValue()], $registeredTeams)]);
            $players = static fn (bool $mine): array => array_map(
                static fn (TeamPlayer $teamPlayer): string => $teamPlayer->getPlayer()->getValue(),
                array_filter($teamPlayers, static fn (TeamPlayer $teamPlayer): bool => ($teamPlayer->getTeam()->getValue() === $teamId->getValue()) === $mine),
            );

            if ([] !== array_intersect($players(true), $players(false))) {
                throw new ConflictException('a player of this team already plays for another team of the tournament');
            }
        }

        return $this->competitorRegistry->enlistTeam($teamId->getValue());
    }

    /**
     * A place in the tournament; the competitor is named by battletag or team name.
     *
     * @param array<string, array{type: string, reference: string, name: ?string}> $described
     *
     * @return array<string, mixed>
     */
    private function normalizeParticipant(Participant $participant, array $described): array
    {
        $competitor = $participant->getCompetitor()->getValue();
        $description = $described[$competitor] ?? null;

        return [
            'id' => ['value' => $participant->getId()->getValue()],
            'tournament' => ['value' => $participant->getTournament()->getValue()],
            'seed' => $participant->getSeed(),
            'createdAt' => $participant->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'competitor' => ['value' => $competitor],
            'type' => $description['type'] ?? null,
            'reference' => null === $description ? null : ['value' => $description['reference']],
            'name' => $description['name'] ?? null,
        ];
    }
}
