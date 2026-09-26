<?php

declare(strict_types=1);

namespace App\Competition\Tournament\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryInterface;
use App\Competition\Tournament\Application\Model\WithdrawParticipantCommand;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\ParticipantId;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Repository\ParticipantRepositoryInterface;
use App\Competition\Tournament\Domain\Repository\TournamentRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class WithdrawParticipantHandler
{
    private TournamentRepositoryInterface $tournamentRepository;
    private ParticipantRepositoryInterface $participantRepository;
    private CompetitorRegistryInterface $competitorRegistry;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        TournamentRepositoryInterface $tournamentRepository,
        ParticipantRepositoryInterface $participantRepository,
        CompetitorRegistryInterface $competitorRegistry,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->tournamentRepository = $tournamentRepository;
        $this->participantRepository = $participantRepository;
        $this->competitorRegistry = $competitorRegistry;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(WithdrawParticipantCommand $withdrawParticipantCommand): string
    {
        $tournamentId = new TournamentId($withdrawParticipantCommand->getTournamentId());
        $participantId = new ParticipantId($withdrawParticipantCommand->getParticipantId());

        $tournament = $this->tournamentRepository->findOneBy(['id' => $tournamentId->getValue()]);
        if (!$tournament instanceof Tournament) {
            throw new NotFoundException('tournament not found');
        }

        $leaving = $this->participantRepository->findOneBy([
            'id' => $participantId->getValue(),
            'tournament' => $tournamentId->getValue(),
        ]);
        if (!$leaving instanceof Participant) {
            throw new NotFoundException('this participant is not registered in the tournament');
        }

        // The participant withdraws itself, or the organizer withdraws it.
        $caller = (string) $this->currentUserProvider->getUser()->getId();
        if ($caller !== $tournament->getOrganizer()->getValue()
            && !\in_array($leaving->getCompetitor()->getValue(), $this->competitorRegistry->representedBy($caller), true)) {
            throw new PermissionDeniedException('only the participant or the organizer withdraws a registration');
        }

        $reseeded = Tournament::withdraw(
            $tournament,
            $leaving,
            $this->participantRepository->findBy(['tournament' => $tournamentId->getValue()]),
        );

        $withdrawn = json_encode(
            $this->normalizeParticipant($leaving, $this->competitorRegistry->describe([$leaving->getCompetitor()->getValue()])),
            JSON_THROW_ON_ERROR,
        );

        $this->participantRepository->remove($leaving);
        foreach ($reseeded as $participant) {
            $this->participantRepository->save($participant);
        }
        $this->tournamentRepository->save($tournament);

        foreach ($tournament->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $withdrawn;
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
