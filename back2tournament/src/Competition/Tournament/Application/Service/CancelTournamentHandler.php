<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Tournament\Application\Model\CancelTournamentCommand;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Repository\MatchupRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class CancelTournamentHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private ParticipantRepositoryInterface $participantRepository;
    private MatchupRepositoryInterface $matchupRepository;
    private CompetitorRegistryInterface $competitorRegistry;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        ParticipantRepositoryInterface $participantRepository,
        MatchupRepositoryInterface $matchupRepository,
        CompetitorRegistryInterface $competitorRegistry,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->participantRepository = $participantRepository;
        $this->matchupRepository = $matchupRepository;
        $this->competitorRegistry = $competitorRegistry;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(CancelTournamentCommand $cancelTournamentCommand): string
    {
        $tournamentId = new TournamentId($cancelTournamentCommand->getTournamentId());

        $tournament = $this->tournamentRepository->findOneBy(['id' => $tournamentId->getValue()]);
        if (!$tournament instanceof Tournament) {
            throw new NotFoundException('tournament not found');
        }

        if ($tournament->getOrganizer()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('only the organizer cancels the tournament');
        }

        Tournament::cancel($tournament);

        $this->tournamentRepository->save($tournament);

        foreach ($tournament->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        $participants = $this->participantRepository->findBy(['tournament' => $tournamentId->getValue()]);

        return json_encode(
            TournamentView::detail(
                $tournament,
                $participants,
                $this->matchupRepository->findBy(['tournament' => $tournamentId->getValue()]),
                $this->competitorRegistry->describe(array_map(
                    static fn (Participant $participant): string => $participant->getCompetitor()->getValue(),
                    $participants,
                )),
            ),
            JSON_THROW_ON_ERROR,
        );
    }
}
