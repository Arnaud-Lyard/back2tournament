<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Model;

use App\Competition\Ranking\Domain\Enum\RankingSubject;

final class FindRatingQuery
{
    private RankingSubject $subjectType;

    private string $subjectId;

    private function __construct(RankingSubject $subjectType, string $subjectId)
    {
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
    }

    public static function ofPlayer(string $playerId): self
    {
        return new self(RankingSubject::PLAYER, $playerId);
    }

    public static function ofClan(string $clanId): self
    {
        return new self(RankingSubject::CLAN, $clanId);
    }

    public function getSubjectType(): RankingSubject
    {
        return $this->subjectType;
    }

    public function getSubjectId(): string
    {
        return $this->subjectId;
    }
}
