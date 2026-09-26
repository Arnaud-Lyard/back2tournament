<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\Model;

use App\Competition\Competitor\Domain\Enum\CompetitorType;

final class CreateCompetitorCommand
{
    private CompetitorType $type;

    private string $reference;

    public function getType(): CompetitorType
    {
        return $this->type;
    }

    public function setType(CompetitorType $type): void
    {
        $this->type = $type;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }
}
