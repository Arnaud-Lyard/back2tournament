<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Model;

use App\Competition\Ranking\Domain\Enum\RankingSubject;

/**
 * One page of a game's ranking of player profiles, or of clans, in one of the
 * formats the game is played in: its smallest one when none is asked for.
 */
final class FindRankingQuery
{
    private RankingSubject $subjectType;

    private string $gameId;

    private ?int $teamSize;

    private int $page;

    private int $limit;

    private function __construct(RankingSubject $subjectType, string $gameId, ?int $teamSize, int $page, int $limit)
    {
        $this->subjectType = $subjectType;
        $this->gameId = $gameId;
        $this->teamSize = $teamSize;
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
    }

    public static function ofPlayers(string $gameId, ?int $teamSize, int $page, int $limit): self
    {
        return new self(RankingSubject::PLAYER, $gameId, $teamSize, $page, $limit);
    }

    public static function ofClans(string $gameId, ?int $teamSize, int $page, int $limit): self
    {
        return new self(RankingSubject::CLAN, $gameId, $teamSize, $page, $limit);
    }

    public function getSubjectType(): RankingSubject
    {
        return $this->subjectType;
    }

    public function getGameId(): string
    {
        return $this->gameId;
    }

    /**
     * The format, as the number of players per side; null for the smallest
     * format of the game.
     */
    public function getTeamSize(): ?int
    {
        return $this->teamSize;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
