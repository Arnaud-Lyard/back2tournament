<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Clan\Application;

use App\Competition\Profile\Clan\Application\Model\FindUserClansQuery;
use App\Competition\Profile\Clan\Application\Service\FindUserClansHandler;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FindUserClansHandlerTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const USER = '11111111-1111-4111-8111-111111111111';
    private const LEADING_GAME = '22222222-2222-4222-8222-222222222222';
    private const PLAYING_GAME = '23232323-2323-4232-8232-232323232323';
    private const LEADING_PLAYER = '33333333-3333-4333-8333-333333333333';
    private const PLAYING_PLAYER = '34343434-3434-4343-8343-343434343434';
    private const LED_CLAN = '44444444-4444-4444-8444-444444444444';
    private const JOINED_CLAN = '45454545-4545-4545-8545-454545454545';
    private const OTHER_LEADER = '55555555-5555-4555-8555-555555555555';

    public function test_a_leader_sees_how_many_players_ask_to_join_the_clan_and_a_member_sees_none(): void
    {
        $ledClan = self::aClan(self::LED_CLAN, self::LEADING_GAME, self::LEADING_PLAYER, 'LED');
        $joinedClan = self::aClan(self::JOINED_CLAN, self::PLAYING_GAME, self::OTHER_LEADER, 'JND');

        $memberships = [
            self::leadership($ledClan),
            self::joinRequest($ledClan, '66666666-6666-4666-8666-666666666666'),
            self::joinRequest($ledClan, '67676767-6767-4676-8676-676767676767'),
            self::invitation($ledClan, '68686868-6868-4686-8686-686868686868'),
            self::membership($ledClan, '69696969-6969-4696-8696-696969696969'),
            self::leadership($joinedClan),
            self::membership($joinedClan, self::PLAYING_PLAYER),
            self::joinRequest($joinedClan, '77777777-7777-4777-8777-777777777777'),
        ];

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static fn (Clan $clan): array => ['tag' => $clan->getTag()]
        );

        $handler = new FindUserClansHandler(
            $this->repositoryStub(ClanRepositoryInterface::class, [$ledClan, $joinedClan]),
            $this->repositoryStub(ClanMemberRepositoryInterface::class, $memberships),
            $this->repositoryStub(PlayerRepositoryInterface::class, [
                self::aPlayer(self::LEADING_PLAYER, self::USER, self::LEADING_GAME, 'Leader#1'),
                self::aPlayer(self::PLAYING_PLAYER, self::USER, self::PLAYING_GAME, 'Member#2'),
            ]),
            $this->signedIn(self::USER),
            $normalizer,
        );

        $items = json_decode($handler(new FindUserClansQuery()), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            [['LED', 'leader', 'active', 2], ['JND', 'member', 'active', 0]],
            array_map(
                static fn (array $item): array => [$item['clan']['tag'], $item['membership']['role'], $item['membership']['status'], $item['requests']],
                $items,
            ),
        );
    }

    public function test_a_request_to_join_is_listed_among_the_places_of_the_player_who_sent_it(): void
    {
        $clan = self::aClan(self::JOINED_CLAN, self::PLAYING_GAME, self::OTHER_LEADER, 'JND');

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn(['tag' => 'JND']);

        $handler = new FindUserClansHandler(
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan]),
            $this->repositoryStub(ClanMemberRepositoryInterface::class, [self::leadership($clan), self::joinRequest($clan, self::PLAYING_PLAYER)]),
            $this->repositoryStub(PlayerRepositoryInterface::class, [self::aPlayer(self::PLAYING_PLAYER, self::USER, self::PLAYING_GAME)]),
            $this->signedIn(self::USER),
            $normalizer,
        );

        $items = json_decode($handler(new FindUserClansQuery()), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $items);
        $this->assertSame(['requested', 0], [$items[0]['membership']['status'], $items[0]['requests']]);
    }
}
