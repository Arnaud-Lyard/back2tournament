<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Tournament\Application\Model\CreateTournamentCommand;
use App\Competition\Tournament\Domain\Entity\Matchup;
use App\Competition\Tournament\Domain\Entity\OrganizerId;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use App\Shared\ValueObject\TournamentNameValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class CreateTournamentHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private GameRepositoryInterface $gameRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        GameRepositoryInterface $gameRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->gameRepository = $gameRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(CreateTournamentCommand $createTournamentCommand): string
    {
        $name = new TournamentNameValueObject($createTournamentCommand->getName());
        $gameId = new GameId($createTournamentCommand->getGame());
        $teamSize = new TeamSizeValueObject($createTournamentCommand->getTeamSize());
        $startsAt = self::dateTime($createTournamentCommand->getStartsAt());

        $game = $this->gameRepository->findOneBy(['id' => $gameId->getValue()]);
        if (!$game instanceof Game) {
            throw new NotFoundException('game not found');
        }

        if (!$game->supportsTeamSize($teamSize->getValue())) {
            throw new ValidationException(\sprintf('%s is not played %2$dv%2$d', $game->getTitle(), $teamSize->getValue()));
        }

        $tournament = Tournament::create(
            new TournamentId(Uuid::v4()->toString()),
            $name,
            $gameId,
            $teamSize,
            $createTournamentCommand->getCapacity(),
            new OrganizerId((string) $this->currentUserProvider->getUser()->getId()),
            $startsAt,
        );

        $this->tournamentRepository->save($tournament);

        foreach ($tournament->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode($this->normalizeTournament($tournament, [], [], []), JSON_THROW_ON_ERROR);
    }

    /**
     * An ISO 8601 date-time with its offset, as JavaScript's toISOString() writes it.
     */
    private static function dateTime(string $value): \DateTimeImmutable
    {
        $refusal = 'startsAt must be an ISO 8601 date-time with its offset, such as 2026-10-01T18:00:00+02:00';

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})$/', $value, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new ValidationException($refusal);
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new ValidationException($refusal);
        }
    }

    /**
     * The tournament, its participants by seed and its bracket round by round.
     * Competitors are named by battletag or team name.
     *
     * @param list<Participant>                                                     $participants
     * @param list<Matchup>                                                         $bracket
     * @param array<string, array{type: string, reference: string, name: ?string}> $described    the competitors, keyed by id
     *
     * @return array<string, mixed>
     */
    private function normalizeTournament(Tournament $tournament, array $participants, array $bracket, array $described): array
    {
        usort($participants, static fn (Participant $one, Participant $two): int => $one->getSeed() <=> $two->getSeed());
        usort($bracket, static fn (Matchup $one, Matchup $two): int => [$one->getRound(), $one->getPosition()] <=> [$two->getRound(), $two->getPosition()]);

        $winner = $tournament->getWinner();

        return [
            'id' => ['value' => $tournament->getId()->getValue()],
            'name' => $tournament->getName(),
            'game' => ['value' => $tournament->getGame()->getValue()],
            'teamSize' => $tournament->getTeamSize(),
            'capacity' => $tournament->getCapacity(),
            'participantCount' => \count($participants),
            'status' => $tournament->getStatus()->value,
            'organizer' => ['value' => $tournament->getOrganizer()->getValue()],
            'startsAt' => $tournament->getStartsAt()->format(\DateTimeInterface::ATOM),
            'winner' => null === $winner ? null : $this->normalizeSide($winner, $described),
            'createdAt' => $tournament->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $tournament->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'participants' => array_map(fn (Participant $participant): array => [
                'id' => ['value' => $participant->getId()->getValue()],
                'tournament' => ['value' => $participant->getTournament()->getValue()],
                'seed' => $participant->getSeed(),
                'createdAt' => $participant->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ] + $this->normalizeSide($participant->getCompetitor(), $described), $participants),
            'rounds' => [] === $bracket ? 0 : max(array_map(static fn (Matchup $matchup): int => $matchup->getRound(), $bracket)),
            'matchups' => array_map(fn (Matchup $matchup): array => [
                'id' => ['value' => $matchup->getId()->getValue()],
                'round' => $matchup->getRound(),
                'position' => $matchup->getPosition(),
                'sides' => array_map(
                    fn (?CompetitorId $side): ?array => null === $side ? null : $this->normalizeSide($side, $described),
                    [$matchup->getCompetitorOne(), $matchup->getCompetitorTwo()],
                ),
                'fight' => null === $matchup->getFight() ? null : ['value' => $matchup->getFight()->getValue()],
                'winner' => null === $matchup->getWinner() ? null : ['value' => $matchup->getWinner()->getValue()],
            ], $bracket),
        ];
    }

    /**
     * @param array<string, array{type: string, reference: string, name: ?string}> $described
     *
     * @return array<string, mixed>
     */
    private function normalizeSide(CompetitorId $competitor, array $described): array
    {
        $description = $described[$competitor->getValue()] ?? null;

        return [
            'competitor' => ['value' => $competitor->getValue()],
            'type' => $description['type'] ?? null,
            'reference' => null === $description ? null : ['value' => $description['reference']],
            'name' => $description['name'] ?? null,
        ];
    }
}
