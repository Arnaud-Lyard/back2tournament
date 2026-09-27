<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Elo ratings of player profiles and clans, and the rating change each settled fight made';
    }

    public function up(Schema $schema): void
    {
        // Empty at first: `bin/console app:rankings:rebuild` counts the fights
        // settled before this migration.
        $this->addSql('CREATE TABLE rating (subject_type VARCHAR(16) NOT NULL, subject UUID NOT NULL, game UUID NOT NULL, value INT NOT NULL, fights INT NOT NULL, wins INT NOT NULL, draws INT NOT NULL, losses INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_rating_ranking ON rating (subject_type, game, value)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rating_subject ON rating (subject_type, subject)');
        $this->addSql('CREATE TABLE rating_change (rating UUID NOT NULL, fight UUID NOT NULL, before_value INT NOT NULL, after_value INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_rating_change_fight ON rating_change (fight)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rating_change_fight ON rating_change (rating, fight)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rating_change');
        $this->addSql('DROP TABLE rating');
    }
}
