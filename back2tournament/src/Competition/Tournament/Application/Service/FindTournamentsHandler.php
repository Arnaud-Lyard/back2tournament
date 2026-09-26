<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Tournament\Application\Model\FindTournamentsQuery;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindTournamentsHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private ParticipantRepositoryInterface $participantRepository;
    private CompetitorRegistryInterface $competitorRegistry;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        ParticipantRepositoryInterface $participantRepository,
        CompetitorRegistryInterface $competitorRegistry,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->participantRepository = $participantRepository;
        $this->competitorRegistry = $competitorRegistry;
    }

    public function __invoke(FindTournamentsQuery $findTournamentsQuery): string
    {
        $gameId = null === $findTournamentsQuery->getGameId() ? null : new GameId($findTournamentsQuery->getGameId())->getValue();
        $status = null;
        if (null !== $findTournamentsQuery->getStatus()) {
            $status = TournamentStatus::tryFrom($findTournamentsQuery->getStatus())
                ?? throw new ValidationException(\sprintf('The status <%s> is not a tournament status', $findTournamentsQuery->getStatus()));
        }
        $limit = $findTournamentsQuery->getLimit();

        $tournaments = $this->tournamentRepository->findPage($gameId, $status, $limit, $findTournamentsQuery->getOffset());

        $counts = $this->participantRepository->countByTournament(
            array_map(static fn (Tournament $tournament): string => $tournament->getId()->getValue(), $tournaments)
        );

        $winners = array_values(array_filter(array_map(
            static fn (Tournament $tournament): ?string => $tournament->getWinner()?->getValue(),
            $tournaments,
        )));
        $described = $this->competitorRegistry->describe($winners);

        $total = $this->tournamentRepository->countPage($gameId, $status);

        return json_encode([
            'items' => array_map(
                fn (Tournament $tournament): array => $this->normalizeTournament($tournament, $counts[$tournament->getId()->getValue()] ?? 0, $described),
                $tournaments,
            ),
            'total' => $total,
            'page' => $findTournamentsQuery->getPage(),
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * A tournament as the list shows it; a winner is named by battletag or team name.
     *
     * @param array<string, array{type: string, reference: string, name: ?string}> $described the winners, keyed by competitor id
     *
     * @return array<string, mixed>
     */
    private function normalizeTournament(Tournament $tournament, int $participantCount, array $described): array
    {
        $winner = $tournament->getWinner();
        $description = null === $winner ? null : ($described[$winner->getValue()] ?? null);

        return [
            'id' => ['value' => $tournament->getId()->getValue()],
            'name' => $tournament->getName(),
            'game' => ['value' => $tournament->getGame()->getValue()],
            'teamSize' => $tournament->getTeamSize(),
            'capacity' => $tournament->getCapacity(),
            'participantCount' => $participantCount,
            'status' => $tournament->getStatus()->value,
            'organizer' => ['value' => $tournament->getOrganizer()->getValue()],
            'startsAt' => $tournament->getStartsAt()->format(\DateTimeInterface::ATOM),
            'winner' => null === $winner ? null : [
                'competitor' => ['value' => $winner->getValue()],
                'type' => $description['type'] ?? null,
                'reference' => null === $description ? null : ['value' => $description['reference']],
                'name' => $description['name'] ?? null,
            ],
            'createdAt' => $tournament->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $tournament->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
