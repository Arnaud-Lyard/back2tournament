<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\FightSchedulerProviderInterface;
use App\Competition\Tournament\Application\Model\AdvanceTournamentCommand;
use App\Competition\Tournament\Domain\Entity\Matchup;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Enum\TournamentStatus;
use App\Competition\Tournament\Domain\Repository\MatchupRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class AdvanceTournamentHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private MatchupRepositoryInterface $matchupRepository;
    private FightSchedulerProviderInterface $fightSchedulerProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        MatchupRepositoryInterface $matchupRepository,
        FightSchedulerProviderInterface $fightSchedulerProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->matchupRepository = $matchupRepository;
        $this->fightSchedulerProvider = $fightSchedulerProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(AdvanceTournamentCommand $advanceTournamentCommand): void
    {
        if (null === $advanceTournamentCommand->getWinner()) {
            return;
        }

        $fightId = new FightId($advanceTournamentCommand->getFightId());
        $winner = new CompetitorId($advanceTournamentCommand->getWinner());

        $matchup = $this->matchupRepository->findOneBy(['fight' => $fightId->getValue()]);
        if (!$matchup instanceof Matchup) {
            return;
        }

        $tournament = $this->tournamentRepository->findOneBy(['id' => $matchup->getTournament()->getValue()]);
        if (!$tournament instanceof Tournament || TournamentStatus::ONGOING !== $tournament->getStatus()) {
            return;
        }

        $bracket = $this->matchupRepository->findBy(['tournament' => $tournament->getId()->getValue()]);
        foreach ($bracket as $candidate) {
            if ($candidate->getId()->getValue() === $matchup->getId()->getValue()) {
                $matchup = $candidate;
            }
        }

        $next = Tournament::recordWinner($tournament, $bracket, $matchup, $winner);

        $this->matchupRepository->save($matchup);

        if (null !== $next) {
            foreach (Tournament::readyForFight([$next]) as $ready) {
                $fight = $this->fightSchedulerProvider->schedule(
                    $ready->getCompetitorOne()->getValue(),
                    $ready->getCompetitorTwo()->getValue(),
                    $tournament->getGame()->getValue(),
                    $tournament->getTeamSize(),
                    $tournament->getId()->getValue(),
                );
                Tournament::attachFight($tournament, $ready, $fight->getId());
            }

            $this->matchupRepository->save($next);
        }

        $this->tournamentRepository->save($tournament);

        foreach ($tournament->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
