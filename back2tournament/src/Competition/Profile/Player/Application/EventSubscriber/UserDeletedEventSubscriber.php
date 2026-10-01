<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\EventSubscriber;

use App\Authentication\User\Domain\Event\UserDeletedEvent;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UserDeletedEventSubscriber implements EventSubscriberInterface
{
    private PlayerRepositoryInterface $playerRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->playerRepository = $playerRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserDeletedEvent::class => 'forgetProfiles',
        ];
    }

    public function forgetProfiles(UserDeletedEvent $event): void
    {
        foreach ($this->playerRepository->findBy(['user' => $event->getUserId()]) as $player) {
            $this->forget($player);
        }
    }

    private function forget(Player $player): void
    {
        $playerId = $player->getId()->getValue();

        foreach ($this->clanMemberRepository->findBy(['player' => $playerId]) as $place) {
            $this->clanMemberRepository->remove($place);
        }

        $onRecord = [] !== $this->competitorRegistryProvider->competitorsOfPlayer($playerId)
            || [] !== $this->teamPlayerRepository->findBy(['player' => $playerId]);

        if ($onRecord) {
            Player::anonymize($player);
            $this->playerRepository->save($player);

            return;
        }

        Player::delete($player);
        $this->playerRepository->remove($player);

        foreach ($player->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
