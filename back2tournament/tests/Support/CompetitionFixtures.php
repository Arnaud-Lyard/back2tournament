<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMember;
use App\Competition\Profile\Clan\Domain\Entity\ClanMemberId;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\GameId as PlayerGameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayer;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayerId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Shared\ValueObject\ClanNameValueObject;
use App\Shared\ValueObject\ClanTagValueObject;
use App\Shared\ValueObject\TeamNameValueObject;
use App\Shared\ValueObject\TeamSizeValueObject;
use Symfony\Component\Uid\Uuid;

trait CompetitionFixtures
{
    private function signedIn(string $userId): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getUser')->willReturn(new User($userId));

        return $currentUserProvider;
    }

    /** @param list<int> $teamSizes */
    private static function aGame(string $gameId, array $teamSizes = [1]): Game
    {
        return Game::create(
            new GameId($gameId),
            'Rocket League',
            array_map(static fn (int $size): TeamSizeValueObject => new TeamSizeValueObject($size), $teamSizes),
        );
    }

    private static function aPlayer(string $playerId, string $userId, string $gameId, string $battletag = 'Player#1234'): Player
    {
        return Player::create(new PlayerId($playerId), $battletag, new PlayerGameId($gameId), new UserId($userId));
    }

    private static function aClan(string $clanId, string $gameId, string $leaderId, string $tag = 'B2T'): Clan
    {
        return Clan::create(new ClanId($clanId), new ClanNameValueObject('Back to Tournament'), new ClanTagValueObject($tag), new GameId($gameId), new PlayerId($leaderId));
    }

    private static function leadership(Clan $clan): ClanMember
    {
        return Clan::createLeaderMembership($clan, new ClanMemberId(Uuid::v4()->toString()));
    }

    private static function invitation(Clan $clan, string $playerId): ClanMember
    {
        return Clan::invite($clan, new ClanMemberId(Uuid::v4()->toString()), new PlayerId($playerId));
    }

    private static function joinRequest(Clan $clan, string $playerId): ClanMember
    {
        return Clan::request($clan, new ClanMemberId(Uuid::v4()->toString()), new PlayerId($playerId));
    }

    private static function membership(Clan $clan, string $playerId): ClanMember
    {
        return Clan::join($clan, self::invitation($clan, $playerId));
    }

    /**
     * @param list<string> $playerIds
     *
     * @return array{Team, list<TeamPlayer>}
     */
    private static function aTeam(string $teamId, Clan $clan, array $playerIds, string $name = 'Falcons'): array
    {
        $team = Team::create(
            new TeamId($teamId),
            new TeamNameValueObject($name),
            $clan->getId(),
            $clan->getGame(),
            new TeamSizeValueObject(\count($playerIds)),
            new PlayerId($playerIds[0]),
            array_map(static fn (string $playerId): PlayerId => new PlayerId($playerId), $playerIds),
        );

        $lineup = array_map(
            static fn (string $playerId): TeamPlayer => Team::createTeamPlayer($team, new TeamPlayerId(Uuid::v4()->toString()), new PlayerId($playerId)),
            $playerIds,
        );

        return [$team, $lineup];
    }

    private static function aCompetitor(string $competitorId, CompetitorType $type, string $reference): Competitor
    {
        return Competitor::create(new CompetitorId($competitorId), $type, $reference);
    }
}
