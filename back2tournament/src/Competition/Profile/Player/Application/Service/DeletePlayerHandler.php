<?php

declare(strict_types=1);

namespace App\Competition\Profile\Player\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Enum\ClanRole;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Player\Application\Model\DeletePlayerCommand;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorIdProviderInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class DeletePlayerHandler
{
    private PlayerRepositoryInterface $playerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private CompetitorIdProviderInterface $competitorIdProvider;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;

    public function __construct(
        PlayerRepositoryInterface $playerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        CompetitorIdProviderInterface $competitorIdProvider,
        ClanMemberRepositoryInterface $clanMemberRepository,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
    ) {
        $this->playerRepository = $playerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->competitorIdProvider = $competitorIdProvider;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(DeletePlayerCommand $deletePlayerCommand): string
    {
        $playerId = new PlayerId($deletePlayerCommand->getPlayerId());

        $player = $this->playerRepository->findOneBy(['id' => $playerId->getValue()]);
        if (!$player instanceof Player) {
            throw new NotFoundException('player profile not found');
        }

        if ($player->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('this player profile belongs to another account');
        }

        if ($this->competitorIdProvider->takesPartInFights($playerId->getValue())) {
            throw new ConflictException('this player profile takes part in fights and cannot be deleted');
        }

        $places = $this->clanMemberRepository->findBy(['player' => $playerId->getValue()]);
        if ([] !== $places) {
            throw new ConflictException(self::whatHoldsTheProfile($places));
        }

        Player::delete($player);

        $deletedPlayer = $this->serializer->serialize($player, 'json');

        $this->playerRepository->remove($player);

        foreach ($player->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $deletedPlayer;
    }

    /** @param non-empty-list<ClanMember> $places */
    private static function whatHoldsTheProfile(array $places): string
    {
        $holds = static fn (callable $matches): bool => [] !== array_filter($places, $matches);

        return match (true) {
            $holds(static fn (ClanMember $place): bool => ClanRole::LEADER === $place->getRole()) => 'this player profile leads a clan and cannot be deleted',
            $holds(static fn (ClanMember $place): bool => ClanMemberStatus::ACTIVE === $place->getStatus()) => 'this player profile belongs to a clan: leave it first',
            $holds(static fn (ClanMember $place): bool => ClanMemberStatus::INVITED === $place->getStatus()) => 'this player profile is invited to a clan: decline the invitation first',
            default => 'this player profile asks to join a clan: withdraw the request first',
        };
    }
}
