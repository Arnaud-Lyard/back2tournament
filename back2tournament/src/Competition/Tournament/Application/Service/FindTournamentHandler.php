<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Tournament\Application\Model\FindTournamentQuery;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Repository\MatchupRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindTournamentHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private ParticipantRepositoryInterface $participantRepository;
    private MatchupRepositoryInterface $matchupRepository;
    private CompetitorRegistryInterface $competitorRegistry;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        ParticipantRepositoryInterface $participantRepository,
        MatchupRepositoryInterface $matchupRepository,
        CompetitorRegistryInterface $competitorRegistry,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->participantRepository = $participantRepository;
        $this->matchupRepository = $matchupRepository;
        $this->competitorRegistry = $competitorRegistry;
    }

    public function __invoke(FindTournamentQuery $findTournamentQuery): string
    {
        $tournamentId = new TournamentId($findTournamentQuery->getTournamentId());

        $tournament = $this->tournamentRepository->findOneBy(['id' => $tournamentId->getValue()]);
        if (!$tournament instanceof Tournament) {
            throw new NotFoundException('tournament not found');
        }

        $participants = $this->participantRepository->findBy(['tournament' => $tournamentId->getValue()]);
        $bracket = $this->matchupRepository->findBy(['tournament' => $tournamentId->getValue()]);

        $competitors = [];
        foreach ($participants as $participant) {
            $competitors[] = $participant->getCompetitor()->getValue();
        }

        return json_encode(
            TournamentView::detail($tournament, $participants, $bracket, $this->competitorRegistry->describe($competitors)),
            JSON_THROW_ON_ERROR,
        );
    }
}
