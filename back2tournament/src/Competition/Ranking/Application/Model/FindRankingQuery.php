<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Model;

use App\Competition\Ranking\Domain\Enum\RankingSubject;

/**
 * One page of a game's ranking of player profiles, or of clans.
 */
final class FindRankingQuery
{
    private RankingSubject $subjectType;

    private string $gameId;

    private int $page;

    private int $limit;

    private function __construct(RankingSubject $subjectType, string $gameId, int $page, int $limit)
    {
        $this->subjectType = $subjectType;
        $this->gameId = $gameId;
        $this->page = max(1, $page);
        $this->limit = min(50, max(1, $limit));
    }

    public static function ofPlayers(string $gameId, int $page, int $limit): self
    {
        return new self(RankingSubject::PLAYER, $gameId, $page, $limit);
    }

    public static function ofClans(string $gameId, int $page, int $limit): self
    {
        return new self(RankingSubject::CLAN, $gameId, $page, $limit);
    }

    public function getSubjectType(): RankingSubject
    {
        return $this->subjectType;
    }

    public function getGameId(): string
    {
        return $this->gameId;
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
