<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Domain\Entity;

use App\Competition\Profile\Clan\Domain\Enum\ClanMemberStatus;
use App\Competition\Profile\Clan\Domain\Enum\ClanRole;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;

class ClanMember
{
    private string $id;

    private string $clan;

    private string $player;

    private ClanRole $role;

    private ClanMemberStatus $status;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function __construct(ClanMemberId $id)
    {
        $this->id = $id->getValue();
    }

    public function getId(): ClanMemberId
    {
        return new ClanMemberId($this->id);
    }

    public function getClan(): ClanId
    {
        return new ClanId($this->clan);
    }

    public function setClan(ClanId $clan): self
    {
        $this->clan = $clan->getValue();

        return $this;
    }

    public function getPlayer(): PlayerId
    {
        return new PlayerId($this->player);
    }

    public function setPlayer(PlayerId $player): self
    {
        $this->player = $player->getValue();

        return $this;
    }

    public function getRole(): ClanRole
    {
        return $this->role;
    }

    public function setRole(ClanRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getStatus(): ClanMemberStatus
    {
        return $this->status;
    }

    public function setStatus(ClanMemberStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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
}
