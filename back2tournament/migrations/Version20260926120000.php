<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clans, teams as clan lineups, game formats (1v1 to NvN), fights bound to a game and a format, and single-elimination tournaments';
    }

    public function up(Schema $schema): void
    {
        // Every game is played 1v1 until an admin opens other formats.
        $this->addSql('ALTER TABLE game ADD team_sizes JSON DEFAULT \'[1]\' NOT NULL');
        $this->addSql('ALTER TABLE game ALTER team_sizes DROP DEFAULT');

        $this->addSql('CREATE TABLE clan (name VARCHAR(50) NOT NULL, tag VARCHAR(5) NOT NULL, game UUID NOT NULL, leader UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_clan_game_tag ON clan (game, tag)');
        $this->addSql('CREATE TABLE clan_member (clan UUID NOT NULL, player UUID NOT NULL, role VARCHAR(16) NOT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_clan_member ON clan_member (clan, player)');

        // A team now belongs to a clan and plays one game in one format. Teams
        // made before had neither and never competed: nothing can be kept of them.
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM competitor WHERE type = \'team\'') > 0,
            'Some teams have already competed: they cannot be given a clan automatically.',
        );
        $this->addSql('DELETE FROM team_player');
        $this->addSql('DELETE FROM team');
        $this->addSql('ALTER TABLE team ADD clan UUID NOT NULL');
        $this->addSql('ALTER TABLE team ADD game UUID NOT NULL');
        $this->addSql('ALTER TABLE team ADD size INT NOT NULL');

        // Existing fights are 1v1 between player profiles: their game is the one of those profiles.
        $this->addSql('ALTER TABLE fight ADD game UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE fight ADD team_size INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE fight ADD tournament UUID DEFAULT NULL');
        $this->addSql('UPDATE fight SET game = player.game FROM competitor, player WHERE competitor.id = fight.competitor_one AND competitor.type = \'player\' AND player.id = competitor.reference');
        $this->addSql('ALTER TABLE fight ALTER team_size DROP DEFAULT');
        $this->addSql('ALTER TABLE fight ALTER game SET NOT NULL');

        $this->addSql('CREATE TABLE tournament (name VARCHAR(100) NOT NULL, game UUID NOT NULL, team_size INT NOT NULL, capacity INT NOT NULL, status VARCHAR(16) NOT NULL, organizer UUID NOT NULL, starts_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, winner UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE tournament_participant (tournament UUID NOT NULL, competitor UUID NOT NULL, seed INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_tournament_participant ON tournament_participant (tournament, competitor)');
        $this->addSql('CREATE TABLE tournament_matchup (tournament UUID NOT NULL, round INT NOT NULL, position INT NOT NULL, competitor_one UUID DEFAULT NULL, competitor_two UUID DEFAULT NULL, fight UUID DEFAULT NULL, winner UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_tournament_matchup ON tournament_matchup (tournament, round, position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tournament_matchup');
        $this->addSql('DROP TABLE tournament_participant');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('ALTER TABLE fight DROP tournament');
        $this->addSql('ALTER TABLE fight DROP team_size');
        $this->addSql('ALTER TABLE fight DROP game');
        $this->addSql('ALTER TABLE team DROP size');
        $this->addSql('ALTER TABLE team DROP game');
        $this->addSql('ALTER TABLE team DROP clan');
        $this->addSql('DROP TABLE clan_member');
        $this->addSql('DROP TABLE clan');
        $this->addSql('ALTER TABLE game DROP team_sizes');
    }
}
