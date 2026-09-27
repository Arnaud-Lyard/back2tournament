<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'One Elo rating per format (1v1, 2v2…) for player profiles and clans';
    }

    public function up(Schema $schema): void
    {
        // A rating made of every format cannot be split: the rankings start
        // empty, and `bin/console app:rankings:rebuild` counts the settled
        // fights again, each in the ranking of its format.
        $this->addSql('DELETE FROM rating_change');
        $this->addSql('DELETE FROM rating');
        $this->addSql('DROP INDEX idx_rating_ranking');
        $this->addSql('DROP INDEX uniq_rating_subject');
        $this->addSql('ALTER TABLE rating ADD team_size INT NOT NULL');
        $this->addSql('CREATE INDEX idx_rating_ranking ON rating (subject_type, game, team_size, value)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rating_subject ON rating (subject_type, subject, team_size)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM rating_change');
        $this->addSql('DELETE FROM rating');
        $this->addSql('DROP INDEX idx_rating_ranking');
        $this->addSql('DROP INDEX uniq_rating_subject');
        $this->addSql('ALTER TABLE rating DROP team_size');
        $this->addSql('CREATE INDEX idx_rating_ranking ON rating (subject_type, game, value)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rating_subject ON rating (subject_type, subject)');
    }
}
