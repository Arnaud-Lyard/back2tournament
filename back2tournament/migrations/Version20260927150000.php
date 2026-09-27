<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A fight records whether an administrator settled it after a dispute';
    }

    public function up(Schema $schema): void
    {
        // The fights settled so far were settled by their sides.
        $this->addSql('ALTER TABLE fight ADD arbitrated BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fight DROP arbitrated');
    }
}
