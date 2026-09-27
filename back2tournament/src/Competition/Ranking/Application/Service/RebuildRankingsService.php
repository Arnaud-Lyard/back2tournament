<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Service;

use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Ranking\Application\Model\RateFightCommand;
use App\Competition\Ranking\Domain\Repository\RatingChangeRepositoryInterface;
use App\Competition\Ranking\Domain\Repository\RatingRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Counts every settled fight again, from an empty ranking, in the order the
 * fights were settled: an Elo rating depends on that order.
 */
final class RebuildRankingsService
{
    private RatingRepositoryInterface $ratingRepository;
    private RatingChangeRepositoryInterface $ratingChangeRepository;
    private ResultRepositoryInterface $resultRepository;
    private MessageBusInterface $messageBus;

    public function __construct(
        RatingRepositoryInterface $ratingRepository,
        RatingChangeRepositoryInterface $ratingChangeRepository,
        ResultRepositoryInterface $resultRepository,
        MessageBusInterface $messageBus,
    ) {
        $this->ratingRepository = $ratingRepository;
        $this->ratingChangeRepository = $ratingChangeRepository;
        $this->resultRepository = $resultRepository;
        $this->messageBus = $messageBus;
    }

    /**
     * @return array{fights: int, ratings: int} the settled fights replayed, and the ratings they made
     */
    public function rebuild(): array
    {
        $this->ratingChangeRepository->removeAll();
        $this->ratingRepository->removeAll();

        // Both results of a fight settle together: the first one seen dates the fight.
        $fightIds = [];
        $settled = $this->resultRepository->findBy(
            ['status' => [ResultStatus::WIN, ResultStatus::LOSS, ResultStatus::DRAW]],
            ['updatedAt' => 'ASC', 'id' => 'ASC'],
        );
        foreach ($settled as $result) {
            $fightIds[$result->getFight()->getValue()] = true;
        }

        foreach (array_keys($fightIds) as $fightId) {
            $this->messageBus->dispatch(new RateFightCommand($fightId));
        }

        return ['fights' => \count($fightIds), 'ratings' => $this->ratingRepository->count()];
    }
}
