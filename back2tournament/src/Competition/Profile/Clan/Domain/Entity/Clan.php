<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Entity;

use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Enum\ClanRole;
use App\Competition\Profile\Clan\Domain\Event\ClanCreatedEvent;
use App\Competition\Profile\Clan\Domain\Event\ClanDissolvedEvent;
use App\Competition\Profile\Clan\Domain\Event\ClanMemberInvitedEvent;
use App\Competition\Profile\Clan\Domain\Event\ClanMemberJoinedEvent;
use App\Competition\Profile\Clan\Domain\Event\ClanMemberLeftEvent;
use App\Competition\Profile\Clan\Domain\Event\ClanMemberRequestedEvent;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\ClanNameValueObject;
use App\Shared\ValueObject\ClanTagValueObject;
use Symfony\Component\Serializer\Attribute\Ignore;

class Clan extends AggregateRoot
{
    private string $id;

    private string $name;

    private string $tag;

    private string $game;

    private string $leader;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    private ?\DateTimeImmutable $dissolvedAt = null;

    public function __construct(ClanId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ClanId
    {
        return new ClanId($this->id);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getGame(): GameId
    {
        return new GameId($this->game);
    }

    public function getLeader(): PlayerId
    {
        return new PlayerId($this->leader);
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDissolvedAt(): ?\DateTimeImmutable
    {
        return $this->dissolvedAt;
    }

    #[Ignore]
    public function isDissolved(): bool
    {
        return null !== $this->dissolvedAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public static function create(
        ClanId $clanId,
        ClanNameValueObject $name,
        ClanTagValueObject $tag,
        GameId $gameId,
        PlayerId $leader,
    ): self {
        $clan = new self($clanId);
        $clan->name = $name->getValue();
        $clan->tag = $tag->getValue();
        $clan->game = $gameId->getValue();
        $clan->leader = $leader->getValue();
        $clan->setCreatedAt(new \DateTimeImmutable('now'));
        $clan->setUpdatedAt(new \DateTimeImmutable('now'));

        $clan->recordDomainEvent(new ClanCreatedEvent($clanId));

        return $clan;
    }

    public static function dissolve(Clan $clan): Clan
    {
        if ($clan->isDissolved()) {
            throw new ConflictException('this clan is already dissolved');
        }

        $now = new \DateTimeImmutable('now');
        $clan->dissolvedAt = $now;
        $clan->setUpdatedAt($now);

        $clan->recordDomainEvent(new ClanDissolvedEvent($clan->getId()));

        return $clan;
    }

    public static function createLeaderMembership(Clan $clan, ClanMemberId $clanMemberId): ClanMember
    {
        $membership = new ClanMember($clanMemberId);
        $membership->setClan($clan->getId());
        $membership->setPlayer($clan->getLeader());
        $membership->setRole(ClanRole::LEADER);
        $membership->setStatus(ClanMemberStatus::ACTIVE);
        $membership->setCreatedAt(new \DateTimeImmutable('now'));
        $membership->setUpdatedAt(new \DateTimeImmutable('now'));

        $clan->recordDomainEvent(new ClanMemberJoinedEvent($clanMemberId));

        return $membership;
    }

    public static function invite(Clan $clan, ClanMemberId $clanMemberId, PlayerId $playerId): ClanMember
    {
        if ($clan->leader === $playerId->getValue()) {
            throw new ConflictException('the leader already belongs to the clan');
        }

        $membership = new ClanMember($clanMemberId);
        $membership->setClan($clan->getId());
        $membership->setPlayer($playerId);
        $membership->setRole(ClanRole::MEMBER);
        $membership->setStatus(ClanMemberStatus::INVITED);
        $membership->setCreatedAt(new \DateTimeImmutable('now'));
        $membership->setUpdatedAt(new \DateTimeImmutable('now'));

        $clan->recordDomainEvent(new ClanMemberInvitedEvent($clanMemberId));

        return $membership;
    }

    public static function request(Clan $clan, ClanMemberId $clanMemberId, PlayerId $playerId): ClanMember
    {
        if ($clan->leader === $playerId->getValue()) {
            throw new ConflictException('the leader already belongs to the clan');
        }

        $membership = new ClanMember($clanMemberId);
        $membership->setClan($clan->getId());
        $membership->setPlayer($playerId);
        $membership->setRole(ClanRole::MEMBER);
        $membership->setStatus(ClanMemberStatus::REQUESTED);
        $membership->setCreatedAt(new \DateTimeImmutable('now'));
        $membership->setUpdatedAt(new \DateTimeImmutable('now'));

        $clan->recordDomainEvent(new ClanMemberRequestedEvent($clanMemberId));

        return $membership;
    }

    public static function join(Clan $clan, ClanMember $membership): ClanMember
    {
        self::ensureBelongs($clan, $membership);

        if (ClanMemberStatus::ACTIVE === $membership->getStatus()) {
            throw new ConflictException('this player already is a member of the clan');
        }
        if (ClanMemberStatus::REQUESTED === $membership->getStatus()) {
            throw new ConflictException('the clan leader has not accepted this request yet');
        }

        return self::activate($clan, $membership);
    }

    public static function admit(Clan $clan, ClanMember $membership): ClanMember
    {
        self::ensureBelongs($clan, $membership);

        if (ClanMemberStatus::ACTIVE === $membership->getStatus()) {
            throw new ConflictException('this player already is a member of the clan');
        }
        if (ClanMemberStatus::INVITED === $membership->getStatus()) {
            throw new ConflictException('this player was invited: they accept the invitation themselves');
        }

        return self::activate($clan, $membership);
    }

    public static function remove(Clan $clan, ClanMember $membership): ClanMember
    {
        self::ensureBelongs($clan, $membership);

        if (ClanRole::LEADER === $membership->getRole()) {
            throw new ConflictException('the leader cannot leave the clan');
        }

        $clan->setUpdatedAt(new \DateTimeImmutable('now'));

        $clan->recordDomainEvent(new ClanMemberLeftEvent($membership->getId()));

        return $membership;
    }

    private static function activate(Clan $clan, ClanMember $membership): ClanMember
    {
        $membership->setStatus(ClanMemberStatus::ACTIVE);
        $membership->setUpdatedAt(new \DateTimeImmutable('now'));
        $clan->setUpdatedAt(new \DateTimeImmutable('now'));

        $clan->recordDomainEvent(new ClanMemberJoinedEvent($membership->getId()));

        return $membership;
    }

    private static function ensureBelongs(Clan $clan, ClanMember $membership): void
    {
        if ($membership->getClan()->getValue() !== $clan->id) {
            throw new NotFoundException('this player has no place in the clan');
        }
    }
}
