<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The clan each side of a fight played for, recorded on its result';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE result ADD clan UUID DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_result_clan ON result (clan)');

        $this->addSql(<<<'SQL'
            UPDATE result SET clan = team.clan
            FROM competitor, team
            WHERE competitor.id = result.competitor
              AND competitor.type = 'team'
              AND team.id = competitor.reference
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE result SET clan = clan_member.clan
            FROM competitor, clan_member
            WHERE competitor.id = result.competitor
              AND competitor.type = 'player'
              AND clan_member.player = competitor.reference
              AND clan_member.status = 'active'
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_result_clan');
        $this->addSql('ALTER TABLE result DROP clan');
    }
}
