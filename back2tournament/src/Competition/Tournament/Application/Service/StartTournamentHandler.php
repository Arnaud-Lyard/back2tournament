<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Competition\Shared\Domain\Provider\FightSchedulerInterface;
use App\Competition\Tournament\Application\Model\StartTournamentCommand;
use App\Competition\Tournament\Domain\Entity\Matchup;
use App\Competition\Tournament\Domain\Entity\MatchupId;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Repository\MatchupRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Closes registrations, draws the bracket and opens the fights of the first round.
 */
#[AsMessageHandler]
final class StartTournamentHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private ParticipantRepositoryInterface $participantRepository;
    private MatchupRepositoryInterface $matchupRepository;
    private FightSchedulerInterface $fightScheduler;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        ParticipantRepositoryInterface $participantRepository,
        MatchupRepositoryInterface $matchupRepository,
        FightSchedulerInterface $fightScheduler,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->participantRepository = $participantRepository;
        $this->matchupRepository = $matchupRepository;
        $this->fightScheduler = $fightScheduler;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(StartTournamentCommand $startTournamentCommand): string
    {
        $tournamentId = new TournamentId($startTournamentCommand->getTournamentId());

        $tournament = $this->tournamentRepository->findOneBy(['id' => $tournamentId->getValue()]);
        if (!$tournament instanceof Tournament) {
            throw new NotFoundException('tournament not found');
        }

        if ($tournament->getOrganizer()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('only the organizer starts the tournament');
        }

        $participants = $this->participantRepository->findBy(['tournament' => $tournamentId->getValue()], ['seed' => 'ASC']);

        $matchupIds = [];
        for ($count = Tournament::bracketSize(\count($participants)) - 1; $count > 0; --$count) {
            $matchupIds[] = new MatchupId(Uuid::v4()->toString());
        }

        $bracket = Tournament::start($tournament, $participants, $matchupIds);

        foreach ($bracket as $matchup) {
            $this->matchupRepository->save($matchup);
        }

        foreach (Tournament::readyForFight($bracket) as $matchup) {
            $fight = $this->fightScheduler->schedule(
                $matchup->getCompetitorOne()->getValue(),
                $matchup->getCompetitorTwo()->getValue(),
                $tournament->getGame()->getValue(),
                $tournament->getTeamSize(),
                $tournamentId->getValue(),
            );

            $this->matchupRepository->save(Tournament::attachFight($tournament, $matchup, $fight->getId()));
        }

        $this->tournamentRepository->save($tournament);

        foreach ($tournament->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        $competitors = array_map(static fn (Participant $participant): string => $participant->getCompetitor()->getValue(), $participants);

        return json_encode(
            $this->normalizeTournament($tournament, $participants, $bracket, $this->competitorRegistryProvider->describe($competitors)),
            JSON_THROW_ON_ERROR,
        );
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
