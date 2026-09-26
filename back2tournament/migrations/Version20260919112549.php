<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919112549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A comment no longer stores the commenter\'s email';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP email');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment ADD email VARCHAR(255) DEFAULT NULL');
    }
}
