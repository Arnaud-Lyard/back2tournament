<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Competition\Tournament\Application\Model\CreateTournamentCommand;
use App\Competition\Tournament\Domain\Entity\OrganizerId;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Entity\TournamentName;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
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
        $name = new TournamentName($createTournamentCommand->getName());
        $gameId = new GameId($createTournamentCommand->getGame());
        $teamSize = new TeamSize($createTournamentCommand->getTeamSize());
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

        return json_encode(TournamentView::detail($tournament, [], [], []), JSON_THROW_ON_ERROR);
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
}
