<?php

declare(strict_types=1);

namespace App\Competition\Fight\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\FindPendingPlayerResultsQuery;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use App\Shared\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindPendingPlayerResultsHandler
{
    private ResultRepositoryInterface $resultRepository;
    private NormalizerInterface $serializer;
    private CurrentUserProviderInterface $currentUserProvider;
    private CompetitorIdProviderInterface $competitorIdProvider;

    public function __construct(
        ResultRepositoryInterface $resultRepository,
        NormalizerInterface $serializer,
        CurrentUserProviderInterface $currentUserProvider,
        CompetitorIdProviderInterface $competitorIdProvider,
    ) {
        $this->resultRepository = $resultRepository;
        $this->serializer = $serializer;
        $this->currentUserProvider = $currentUserProvider;
        $this->competitorIdProvider = $competitorIdProvider;
    }

    public function __invoke(FindPendingPlayerResultsQuery $findPendingPlayerResultsQuery): string
    {
        $user = $this->currentUserProvider->getUser();

        $competitor = $this->competitorIdProvider->byUserAndGame($user->getId(), $findPendingPlayerResultsQuery->getGameId());

        if (!$competitor) {
            throw new NotFoundException('Competitor not found');
        }

        $results = $this->resultRepository->findBy(
            ['competitor' => $competitor, 'status' => [ResultStatus::PENDING, ResultStatus::REPORTING]],
            ['createdAt' => 'ASC'],
        );

        $normalized = [];
        foreach ($results as $result) {
            $normalized[] = $this->serializer->normalize($result);
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
