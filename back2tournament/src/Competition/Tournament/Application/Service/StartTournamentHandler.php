<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Shared\Domain\Provider\FightSchedulerInterface;
use App\Competition\Tournament\Application\Model\StartTournamentCommand;
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
    private CompetitorRegistryInterface $competitorRegistry;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        ParticipantRepositoryInterface $participantRepository,
        MatchupRepositoryInterface $matchupRepository,
        FightSchedulerInterface $fightScheduler,
        CompetitorRegistryInterface $competitorRegistry,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->participantRepository = $participantRepository;
        $this->matchupRepository = $matchupRepository;
        $this->fightScheduler = $fightScheduler;
        $this->competitorRegistry = $competitorRegistry;
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
            TournamentView::detail($tournament, $participants, $bracket, $this->competitorRegistry->describe($competitors)),
            JSON_THROW_ON_ERROR,
        );
    }
}
