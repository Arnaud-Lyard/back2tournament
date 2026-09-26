<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Service;

use App\Competition\Profile\Team\Application\Model\CreateTeamCommand;
use App\Competition\Profile\Team\Domain\Entity\PlayerId;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayerId;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateTeamHandler
{
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;
    private RequestStack $requestStack;

    public function __construct(
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        RequestStack $requestStack,
    ) {
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->requestStack = $requestStack;
    }

    public function __invoke(CreateTeamCommand $createTeamCommand): void
    {
        $team = Team::create(
            new TeamId(Uuid::v4()->toString()),
            $createTeamCommand->getName(),
            new PlayerId($createTeamCommand->getLeader()),
        );

        $this->teamRepository->save($team);

        $teamPlayer = Team::createTeamPlayer(
            $team,
            new TeamPlayerId(Uuid::v4()->toString()),
            new PlayerId($createTeamCommand->getPlayer()),
        );

        $this->teamPlayerRepository->save($teamPlayer);

        $this->requestStack->getSession()->set(
            'last_team_created',
            $this->serializer->serialize($team, 'json')
        );

        foreach ($team->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
