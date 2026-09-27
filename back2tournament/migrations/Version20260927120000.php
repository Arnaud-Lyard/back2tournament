<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Articles get a draft or published status, a publication date and an author only once published; comments record who wrote them';
    }

    public function up(Schema $schema): void
    {
        // The articles written so far were public from the start: they are
        // published, on the day they were written, by the user who wrote them.
        $this->addSql('ALTER TABLE article ADD status VARCHAR(16) DEFAULT \'published\' NOT NULL');
        $this->addSql('ALTER TABLE article ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE article ADD published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE article SET published_at = created_at');
        // A draft has no author until someone publishes it.
        $this->addSql('ALTER TABLE article ALTER author DROP NOT NULL');

        // Older comments never recorded their author: they keep none.
        $this->addSql('ALTER TABLE comment ADD author UUID DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM article WHERE author IS NULL') > 0,
            'Some articles are drafts without an author: publish or delete them first.',
        );

        $this->addSql('ALTER TABLE comment DROP author');
        $this->addSql('ALTER TABLE article ALTER author SET NOT NULL');
        $this->addSql('ALTER TABLE article DROP published_at');
        $this->addSql('ALTER TABLE article DROP status');
    }
}
