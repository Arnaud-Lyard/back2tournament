<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Articles get an optional English version: a title and a body, or neither';
    }

    public function up(Schema $schema): void
    {
        // The articles written so far are in French only.
        $this->addSql('ALTER TABLE article ADD title_en VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD body_en TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP title_en');
        $this->addSql('ALTER TABLE article DROP body_en');
    }
}
