<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\CreateFightCommand;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Repository\GameRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Competition\Shared\Domain\Provider\FightSchedulerProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Opens a challenge: 1v1 between two player profiles, or NvN between two teams
 * of the same format. The caller stands on one of the two sides.
 */
#[AsMessageHandler]
final class CreateFightHandler
{
    private PlayerRepositoryInterface $playerRepository;
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private GameRepositoryInterface $gameRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private FightSchedulerProviderInterface $fightSchedulerProvider;
    private CurrentUserProviderInterface $currentUserProvider;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        GameRepositoryInterface $gameRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        FightSchedulerProviderInterface $fightSchedulerProvider,
        CurrentUserProviderInterface $currentUserProvider,
    ) {
        $this->playerRepository = $playerRepository;
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->gameRepository = $gameRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->fightSchedulerProvider = $fightSchedulerProvider;
        $this->currentUserProvider = $currentUserProvider;
    }

    public function __invoke(CreateFightCommand $createFightCommand): string
    {
        [$competitorOne, $competitorTwo, $gameId, $teamSize] = $createFightCommand->isBetweenTeams()
            ? $this->betweenTeams(new TeamId($createFightCommand->getSideOne()), new TeamId($createFightCommand->getSideTwo()))
            : $this->betweenPlayers(new PlayerId($createFightCommand->getSideOne()), new PlayerId($createFightCommand->getSideTwo()));

        $fight = $this->fightSchedulerProvider->schedule($competitorOne, $competitorTwo, $gameId, $teamSize);

        return json_encode(
            $this->normalizeFight(
                $fight,
                [],
                $this->competitorRegistryProvider->describe([$competitorOne, $competitorTwo]),
                $this->competitorRegistryProvider->representedBy($this->caller()),
            ),
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array{string, string, string, int} both competitors, the game and the format
     */
    private function betweenPlayers(PlayerId $one, PlayerId $two): array
    {
        if ($one->getValue() === $two->getValue()) {
            throw new ValidationException('a player cannot fight itself');
        }

        $playerOne = $this->playerRepository->findOneBy(['id' => $one->getValue()]);
        $playerTwo = $this->playerRepository->findOneBy(['id' => $two->getValue()]);
        if (!$playerOne instanceof Player || !$playerTwo instanceof Player) {
            throw new NotFoundException('player not found');
        }

        if ($playerOne->getGame()->getValue() !== $playerTwo->getGame()->getValue()) {
            throw new ValidationException('both players must play the same game');
        }

        // A fight is opened by one of its two sides, never by a bystander.
        $caller = $this->caller();
        if ($caller !== $playerOne->getUser()->getValue() && $caller !== $playerTwo->getUser()->getValue()) {
            throw new PermissionDeniedException('you do not take part in this fight');
        }

        $gameId = $playerOne->getGame()->getValue();
        $this->ensurePlayedIn($gameId, 1);

        return [
            $this->competitorRegistryProvider->enlistPlayer($one->getValue()),
            $this->competitorRegistryProvider->enlistPlayer($two->getValue()),
            $gameId,
            1,
        ];
    }

    /**
     * @return array{string, string, string, int} both competitors, the game and the format
     */
    private function betweenTeams(TeamId $one, TeamId $two): array
    {
        if ($one->getValue() === $two->getValue()) {
            throw new ValidationException('a team cannot fight itself');
        }

        $teamOne = $this->teamRepository->findOneBy(['id' => $one->getValue()]);
        $teamTwo = $this->teamRepository->findOneBy(['id' => $two->getValue()]);
        if (!$teamOne instanceof Team || !$teamTwo instanceof Team) {
            throw new NotFoundException('team not found');
        }

        if ($teamOne->getGame()->getValue() !== $teamTwo->getGame()->getValue() || $teamOne->getSize() !== $teamTwo->getSize()) {
            throw new ValidationException('both teams must play the same game, in the same format');
        }

        // A team is spoken for by its leader.
        $caller = $this->caller();
        $leaders = $this->playerRepository->findBy(['id' => [$teamOne->getLeader()->getValue(), $teamTwo->getLeader()->getValue()]]);
        if ([] === array_filter($leaders, static fn (Player $leader): bool => $leader->getUser()->getValue() === $caller)) {
            throw new PermissionDeniedException('only the leader of one of the two teams opens this fight');
        }

        $lineups = [$one->getValue() => [], $two->getValue() => []];
        foreach ($this->teamPlayerRepository->findBy(['team' => array_keys($lineups)]) as $teamPlayer) {
            $lineups[$teamPlayer->getTeam()->getValue()][] = $teamPlayer->getPlayer()->getValue();
        }
        if ([] !== array_intersect($lineups[$one->getValue()], $lineups[$two->getValue()])) {
            throw new ValidationException('a player cannot stand on both sides of a fight');
        }

        $gameId = $teamOne->getGame()->getValue();
        $this->ensurePlayedIn($gameId, $teamOne->getSize());

        return [
            $this->competitorRegistryProvider->enlistTeam($one->getValue()),
            $this->competitorRegistryProvider->enlistTeam($two->getValue()),
            $gameId,
            $teamOne->getSize(),
        ];
    }

    private function ensurePlayedIn(string $gameId, int $teamSize): void
    {
        $game = $this->gameRepository->findOneBy(['id' => $gameId]);
        if (!$game instanceof Game) {
            throw new NotFoundException('game not found');
        }

        if (!$game->supportsTeamSize($teamSize)) {
            throw new ValidationException(\sprintf('%s is not played %2$dv%2$d', $game->getTitle(), $teamSize));
        }
    }

    private function caller(): string
    {
        return (string) $this->currentUserProvider->getUser()->getId();
    }

    /**
     * The fight, where it stands as a whole, and its two sides, each named and
     * carrying its own result.
     *
     * @param list<Result>                                                         $results     a side with no result reads as pending, 0 points
     * @param array<string, array{type: string, reference: string, name: ?string, tag: ?string}> $described   the sides, keyed by competitor id
     * @param list<string>                                                         $represented the competitors the caller speaks for
     *
     * @return array<string, mixed>
     */
    private function normalizeFight(Fight $fight, array $results, array $described, array $represented): array
    {
        $byCompetitor = [];
        foreach ($results as $result) {
            $byCompetitor[$result->getCompetitor()->getValue()] = $result;
        }

        $sides = [];
        $statuses = [];
        $winner = null;
        $mySide = null;
        foreach ([$fight->getCompetitorOne()->getValue(), $fight->getCompetitorTwo()->getValue()] as $competitor) {
            $result = $byCompetitor[$competitor] ?? null;
            $status = $result?->getStatus() ?? ResultStatus::PENDING;
            $statuses[] = $status;

            if (ResultStatus::WIN === $status) {
                $winner = ['value' => $competitor];
            }

            if (null === $mySide && \in_array($competitor, $represented, true)) {
                $mySide = ['value' => $competitor];
            }

            $sides[] = [
                'competitor' => ['value' => $competitor],
                'type' => $described[$competitor]['type'] ?? null,
                'reference' => isset($described[$competitor]) ? ['value' => $described[$competitor]['reference']] : null,
                'name' => $described[$competitor]['name'] ?? null,
                'tag' => $described[$competitor]['tag'] ?? null,
                'score' => $result?->getScore() ?? 0,
                'status' => $status->value,
                'reportedStatus' => $result?->getReportedStatus()?->value,
            ];
        }

        $status = 'finished';
        if (\in_array(ResultStatus::REPORTING, $statuses, true)) {
            $status = 'reporting';
        } elseif (\in_array(ResultStatus::PENDING, $statuses, true)) {
            $status = 'pending';
        }

        return [
            'id' => ['value' => $fight->getId()->getValue()],
            'game' => ['value' => $fight->getGame()->getValue()],
            'teamSize' => $fight->getTeamSize(),
            'tournament' => null === $fight->getTournament() ? null : ['value' => $fight->getTournament()->getValue()],
            'status' => $status,
            'declaredBy' => null === $fight->getDeclaredBy() ? null : ['value' => $fight->getDeclaredBy()->getValue()],
            'arbitrated' => $fight->isArbitrated(),
            'winner' => $winner,
            'mySide' => $mySide,
            'sides' => $sides,
            'createdAt' => $fight->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $fight->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
