<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deletions: a dissolved clan is kept for the record and frees its tag, an erased account is kept anonymized, as are the player profiles that fought';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_clan_game_tag');
        $this->addSql('ALTER TABLE clan ADD dissolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_clan_game_tag ON clan (game, tag) WHERE (dissolved_at IS NULL)');
        $this->addSql('ALTER TABLE player ADD anonymized_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_clan_game_tag');
        $this->addSql('ALTER TABLE clan DROP dissolved_at');
        $this->addSql('CREATE UNIQUE INDEX uniq_clan_game_tag ON clan (game, tag)');
        $this->addSql('ALTER TABLE player DROP anonymized_at');
        $this->addSql('ALTER TABLE users DROP deleted_at');
    }
}
