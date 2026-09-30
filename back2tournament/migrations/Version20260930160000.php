<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'API tokens: the tools that write drafts through /api/bot, each kept as the hash of its token';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_token (name VARCHAR(40) NOT NULL, token_hash VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_api_token_name ON api_token (name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_api_token_hash ON api_token (token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_token');
    }
}
