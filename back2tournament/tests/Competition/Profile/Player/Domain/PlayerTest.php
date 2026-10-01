<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Domain;

use App\Competition\Profile\Player\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Player\Domain\Event\PlayerCreatedEvent;
use App\Competition\Profile\Player\Domain\Event\PlayerDeletedEvent;
use App\Competition\Profile\Player\Domain\Event\PlayerUpdatedEvent;
use PHPUnit\Framework\TestCase;

final class PlayerTest extends TestCase
{
    private const PLAYER_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const USER_ID = '33333333-3333-4333-8333-333333333333';

    public function test_an_anonymized_profile_keeps_its_game_and_account_under_an_anonymous_battletag(): void
    {
        $player = Player::create(new PlayerId(self::PLAYER_ID), 'PlayerOne#1234', new GameId(self::GAME_ID), new UserId(self::USER_ID));
        $player->pullDomainEvents();

        Player::anonymize($player);

        $this->assertMatchesRegularExpression('/^Anonyme#\d{4}$/', (string) $player->getBattletag());
        $this->assertNotNull($player->getAnonymizedAt());
        $this->assertSame([self::GAME_ID, self::USER_ID], [$player->getGame()->getValue(), $player->getUser()->getValue()]);
        $this->assertSame([], $player->pullDomainEvents());
    }

    public function test_a_new_profile_carries_its_battletag_game_and_account(): void
    {
        $player = $this->player();

        self::assertSame(self::PLAYER_ID, $player->getId()->getValue());
        self::assertSame('PlayerOne#1234', $player->getBattletag());
        self::assertSame(self::GAME_ID, $player->getGame()->getValue());
        self::assertSame(self::USER_ID, $player->getUser()->getValue());
        self::assertInstanceOf(PlayerCreatedEvent::class, $player->pullDomainEvents()[0]);
    }

    public function test_updating_a_profile_replaces_its_battletag(): void
    {
        $player = $this->player();
        $player->pullDomainEvents();

        Player::update($player, 'PlayerTwo#5678');

        self::assertSame('PlayerTwo#5678', $player->getBattletag());
    }

    public function test_updating_a_profile_touches_neither_its_game_nor_its_account(): void
    {
        $player = $this->player();

        Player::update($player, 'PlayerTwo#5678');

        self::assertSame(self::GAME_ID, $player->getGame()->getValue());
        self::assertSame(self::USER_ID, $player->getUser()->getValue());
    }

    public function test_updating_a_profile_records_the_fact_against_its_own_id(): void
    {
        $player = $this->player();
        $player->pullDomainEvents();

        Player::update($player, 'PlayerTwo#5678');

        $domainEvents = $player->pullDomainEvents();
        self::assertCount(1, $domainEvents);
        self::assertInstanceOf(PlayerUpdatedEvent::class, $domainEvents[0]);
        self::assertSame(self::PLAYER_ID, $domainEvents[0]->getPlayerId()->getValue());
    }

    public function test_updating_a_profile_moves_its_updated_at_but_not_its_created_at(): void
    {
        $player = $this->player();
        $createdAt = $player->getCreatedAt();
        $player->setUpdatedAt(new \DateTimeImmutable('2020-01-01 00:00:00'));

        Player::update($player, 'PlayerTwo#5678');

        self::assertSame($createdAt, $player->getCreatedAt());
        self::assertGreaterThan(new \DateTimeImmutable('2020-01-01 00:00:00'), $player->getUpdatedAt());
    }

    public function test_deleting_a_profile_records_the_fact_against_its_own_id(): void
    {
        $player = $this->player();
        $player->pullDomainEvents();

        Player::delete($player);

        $domainEvents = $player->pullDomainEvents();
        self::assertCount(1, $domainEvents);
        self::assertInstanceOf(PlayerDeletedEvent::class, $domainEvents[0]);
        self::assertSame(self::PLAYER_ID, $domainEvents[0]->getPlayerId()->getValue());
    }

    private function player(): Player
    {
        return Player::create(
            new PlayerId(self::PLAYER_ID),
            'PlayerOne#1234',
            new GameId(self::GAME_ID),
            new UserId(self::USER_ID),
        );
    }
}
