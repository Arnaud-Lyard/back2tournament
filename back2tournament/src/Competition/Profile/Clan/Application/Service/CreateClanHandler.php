<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\CreateClanCommand;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMemberId;
use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\ClanNameValueObject;
use App\Shared\ValueObject\ClanTagValueObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class CreateClanHandler
{
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private PlayerRepositoryInterface $playerRepository;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private SerializerInterface $serializer;

    public function __construct(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        PlayerRepositoryInterface $playerRepository,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
    ) {
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->playerRepository = $playerRepository;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(CreateClanCommand $createClanCommand): string
    {
        $gameId = new GameId($createClanCommand->getGame());
        $name = new ClanNameValueObject($createClanCommand->getName());
        $tag = new ClanTagValueObject($createClanCommand->getTag());

        $founder = $this->playerRepository->findOneBy([
            'user' => (string) $this->currentUserProvider->getUser()->getId(),
            'game' => $gameId->getValue(),
        ]);
        if (!$founder instanceof Player) {
            throw new NotFoundException('you hold no player profile in this game');
        }

        if (null !== $this->clanMemberRepository->findOneBy([
            'player' => $founder->getId()->getValue(),
            'status' => ClanMemberStatus::ACTIVE,
        ])) {
            throw new ConflictException('you already belong to a clan in this game');
        }

        if (null !== $this->clanRepository->findOneBy(['game' => $gameId->getValue(), 'tag' => $tag->getValue(), 'dissolvedAt' => null])) {
            throw new ConflictException(\sprintf('the tag %s is already taken in this game', $tag->getValue()));
        }

        $clan = Clan::create(new ClanId(Uuid::v4()->toString()), $name, $tag, $gameId, $founder->getId());

        $this->clanRepository->save($clan);
        $this->clanMemberRepository->save(
            Clan::createLeaderMembership($clan, new ClanMemberId(Uuid::v4()->toString()))
        );

        foreach ($clan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return $this->serializer->serialize($clan, 'json');
    }
}
