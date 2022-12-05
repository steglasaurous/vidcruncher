<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221204143751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media ADD added TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE media DROP added');
    }
}
