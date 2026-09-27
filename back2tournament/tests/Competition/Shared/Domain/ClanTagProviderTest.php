<?php

declare(strict_types=1);

namespace App\Tests\Competition\Shared\Domain;

use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Shared\Domain\Provider\ClanTagProvider;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;

final class ClanTagProviderTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const LEADER = '22222222-2222-4222-8222-222222222222';
    private const MEMBER = '33333333-3333-4333-8333-333333333333';
    private const INVITED = '44444444-4444-4444-8444-444444444444';
    private const LONER = '55555555-5555-4555-8555-555555555555';
    private const CLAN_ID = '66666666-6666-4666-8666-666666666666';
    private const OTHER_CLAN = '77777777-7777-4777-8777-777777777777';
    private const UNKNOWN_CLAN = '88888888-8888-4888-8888-888888888888';

    public function test_the_leader_and_the_members_play_under_the_tag_of_their_clan(): void
    {
        $clans = $this->provider()->clansOfPlayers([self::LEADER, self::MEMBER]);

        $this->assertSame([
            self::LEADER => ['id' => self::CLAN_ID, 'tag' => 'B2T'],
            self::MEMBER => ['id' => self::CLAN_ID, 'tag' => 'B2T'],
        ], $clans);
    }

    public function test_a_player_only_invited_or_in_no_clan_has_no_tag(): void
    {
        $this->assertSame([], $this->provider()->clansOfPlayers([self::INVITED, self::LONER]));
        $this->assertSame([], $this->provider()->clansOfPlayers([]));
    }

    public function test_a_clan_is_known_by_its_tag_and_an_unknown_one_is_left_out(): void
    {
        $this->assertSame(
            [self::CLAN_ID => 'B2T', self::OTHER_CLAN => 'OTH'],
            $this->provider()->tagsOfClans([self::CLAN_ID, self::OTHER_CLAN, self::UNKNOWN_CLAN, self::CLAN_ID]),
        );
    }

    private function provider(): ClanTagProvider
    {
        $clan = self::aClan(self::CLAN_ID, self::GAME_ID, self::LEADER, 'B2T');
        $other = self::aClan(self::OTHER_CLAN, self::GAME_ID, self::LONER, 'OTH');

        return new ClanTagProvider(
            $this->repositoryStub(ClanMemberRepositoryInterface::class, [
                self::leadership($clan),
                self::membership($clan, self::MEMBER),
                self::invitation($other, self::INVITED),
            ]),
            $this->repositoryStub(ClanRepositoryInterface::class, [$clan, $other]),
        );
    }
}
