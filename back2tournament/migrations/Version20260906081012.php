<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906081012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A fight result is reported by one side and confirmed by the other';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight ADD declared_by UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE result ADD reported_status VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight DROP declared_by');
        $this->addSql('ALTER TABLE result DROP reported_status');
    }
}
