<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Images: an article cover, a user avatar and a game picture, each kept as the key of the stored image';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD avatar VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP image');
        $this->addSql('ALTER TABLE users DROP avatar');
        $this->addSql('ALTER TABLE game DROP image');
    }
}
