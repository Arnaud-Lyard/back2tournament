<?php

declare(strict_types=1);

namespace App\Competition\Competitor\Application\Service;

use App\Competition\Competitor\Application\Model\CreateCompetitorCommand;
use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Repository\CompetitorRepositoryInterface;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CreateCompetitorHandler
{
    private CompetitorRepositoryInterface $competitorRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CompetitorRepositoryInterface $competitorRepository,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->competitorRepository = $competitorRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(CreateCompetitorCommand $createCompetitorCommand): void
    {
        $competitor = Competitor::create(
            new CompetitorId(Uuid::v4()->toString()),
            $createCompetitorCommand->getType(),
            $createCompetitorCommand->getReference(),
        );

        $this->competitorRepository->save($competitor);

        foreach ($competitor->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
