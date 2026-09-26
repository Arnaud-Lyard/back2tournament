<?php

declare(strict_types=1);

namespace App\Competition\Profile\Team\Application\Service;

use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Application\Model\FindTeamQuery;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class FindTeamHandler
{
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private PlayerRepositoryInterface $playerRepository;

    public function __construct(
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        PlayerRepositoryInterface $playerRepository,
    ) {
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->playerRepository = $playerRepository;
    }

    public function __invoke(FindTeamQuery $findTeamQuery): string
    {
        $teamId = new TeamId($findTeamQuery->getTeamId());

        $team = $this->teamRepository->findOneBy(['id' => $teamId->getValue()]);
        if (!$team instanceof Team) {
            throw new NotFoundException('team not found');
        }

        $playerIds = [];
        foreach ($this->teamPlayerRepository->findBy(['team' => $teamId->getValue()]) as $teamPlayer) {
            $playerIds[] = $teamPlayer->getPlayer()->getValue();
        }

        $players = [] === $playerIds ? [] : $this->playerRepository->findBy(['id' => $playerIds]);

        return json_encode(TeamView::of($team, $players), JSON_THROW_ON_ERROR);
    }
}
