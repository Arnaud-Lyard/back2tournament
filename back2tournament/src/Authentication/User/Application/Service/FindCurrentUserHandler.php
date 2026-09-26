<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\Service;

use App\Authentication\User\Application\Model\FindCurrentUserQuery;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Shared\Domain\Provider\PlayerProfileProviderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsMessageHandler]
final class FindCurrentUserHandler
{
    private CurrentUserProviderInterface $currentUserProvider;
    private PlayerProfileProviderInterface $playerProfileProvider;
    private NormalizerInterface $serializer;

    public function __construct(
        CurrentUserProviderInterface $currentUserProvider,
        PlayerProfileProviderInterface $playerProfileProvider,
        NormalizerInterface $serializer,
    ) {
        $this->currentUserProvider = $currentUserProvider;
        $this->playerProfileProvider = $playerProfileProvider;
        $this->serializer = $serializer;
    }

    public function __invoke(FindCurrentUserQuery $findCurrentUserQuery): string
    {
        $user = $this->currentUserProvider->getUser();

        /** @var array<string, mixed> $currentUser */
        $currentUser = $this->serializer->normalize($user);
        $currentUser['players'] = $this->playerProfileProvider->byUser((string) $user->getId());

        return json_encode($currentUser, JSON_THROW_ON_ERROR);
    }
}
