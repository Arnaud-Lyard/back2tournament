<?php

declare(strict_types=1);

namespace App\Tests\Competition\Profile\Player\Application;

use App\Authentication\User\Domain\Event\UserDeletedEvent;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Player\Application\EventSubscriber\UserDeletedEventSubscriber;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Tests\Support\CompetitionFixtures;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UserDeletedEventSubscriberTest extends TestCase
{
    use CompetitionFixtures;
    use RepositoryStubs;

    private const USER_ID = '11111111-1111-4111-8111-111111111111';
    private const OTHER_USER = '12121212-1212-4121-8121-121212121212';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const OTHER_GAME = '23232323-2323-4232-8232-232323232323';
    private const THIRD_GAME = '24242424-2424-4242-8242-242424242424';
    private const FOUGHT = '33333333-3333-4333-8333-333333333333';
    private const FIELDED = '34343434-3434-4343-8343-343434343434';
    private const NEVER_PLAYED = '35353535-3535-4353-8353-353535353535';
    private const SOMEONE_ELSE = '36363636-3636-4363-8363-363636363636';
    private const CLAN_ID = '44444444-4444-4444-8444-444444444444';
    private const LEADER = '45454545-4545-4545-8545-454545454545';
    private const TEAM_ID = '55555555-5555-4555-8555-555555555555';

    public function test_profiles_on_record_are_anonymized_the_others_go_and_every_clan_place_is_left(): void
    {
        $fought = self::aPlayer(self::FOUGHT, self::USER_ID, self::GAME_ID, 'Demo#1000');
        $fielded = self::aPlayer(self::FIELDED, self::USER_ID, self::OTHER_GAME, 'Demo#1001');
        $neverPlayed = self::aPlayer(self::NEVER_PLAYED, self::USER_ID, self::THIRD_GAME, 'Demo#1002');
        $someoneElse = self::aPlayer(self::SOMEONE_ELSE, self::OTHER_USER, self::GAME_ID, 'Rival#2000');

        $clan = self::aClan(self::CLAN_ID, self::OTHER_GAME, self::LEADER);
        [, $lineup] = self::aTeam(self::TEAM_ID, $clan, [self::LEADER, self::FIELDED]);
        $places = [
            self::membership($clan, self::FIELDED),
            self::invitation($clan, self::NEVER_PLAYED),
            self::membership($clan, self::SOMEONE_ELSE),
        ];

        $saved = [];
        $removed = [];
        $playerRepository = $this->repositoryStub(PlayerRepositoryInterface::class, [$fought, $fielded, $neverPlayed, $someoneElse]);
        $playerRepository->method('save')->willReturnCallback(
            static function (Player $player) use (&$saved): void {
                $saved[] = $player->getId()->getValue();
            }
        );
        $playerRepository->method('remove')->willReturnCallback(
            static function (Player $player) use (&$removed): void {
                $removed[] = $player->getId()->getValue();
            }
        );

        $leftPlaces = [];
        $clanMemberRepository = $this->repositoryStub(ClanMemberRepositoryInterface::class, $places);
        $clanMemberRepository->method('remove')->willReturnCallback(
            static function (ClanMember $place) use (&$leftPlaces): void {
                $leftPlaces[] = $place->getPlayer()->getValue();
            }
        );

        $registry = $this->createStub(CompetitorRegistryProviderInterface::class);
        $registry->method('competitorsOfPlayer')->willReturnCallback(
            static fn (string $playerId): array => self::FOUGHT === $playerId ? ['66666666-6666-4666-8666-666666666666'] : []
        );

        new UserDeletedEventSubscriber(
            $playerRepository,
            $clanMemberRepository,
            $this->repositoryStub(TeamPlayerRepositoryInterface::class, $lineup),
            $registry,
            $this->createStub(EventDispatcherInterface::class),
        )->forgetProfiles(new UserDeletedEvent(self::USER_ID));

        $this->assertSame([self::FOUGHT, self::FIELDED], $saved);
        $this->assertSame([self::NEVER_PLAYED], $removed);
        $this->assertSame([self::FIELDED, self::NEVER_PLAYED], $leftPlaces);
        $this->assertNotNull($fought->getAnonymizedAt());
        $this->assertStringStartsWith('Anonyme#', (string) $fielded->getBattletag());
        $this->assertNull($someoneElse->getAnonymizedAt());
    }
}
