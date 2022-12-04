<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221203233014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE media_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE media_file_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE profile_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE project_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE media (id INT NOT NULL, project_id INT NOT NULL, start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(255) NOT NULL, worker_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6A2CA10C166D1F9C ON media (project_id)');
        $this->addSql('CREATE TABLE media_file (id INT NOT NULL, media_id INT NOT NULL, media_type VARCHAR(255) NOT NULL, media_path VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4FD8E9C3EA9FDD75 ON media_file (media_id)');
        $this->addSql('CREATE TABLE profile (id INT NOT NULL, name VARCHAR(255) NOT NULL, preset INT NOT NULL, crf INT NOT NULL, input_path VARCHAR(255) NOT NULL, 
output_path VARCHAR(255) NOT NULL, 
is_live_recordings BOOLEAN NOT NULL, 
process_modified_older_than INT NOT NULL, 
assemble_after_time INT NOT NULL, 
is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE project (id INT NOT NULL, profile_id INT NOT NULL, start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(255) NOT NULL, origin_file_path VARCHAR(255) DEFAULT NULL, output_filename VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2FB3D0EECCFA12B8 ON project (profile_id)');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE media_file ADD CONSTRAINT FK_4FD8E9C3EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EECCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
	$this->addSql('INSERT INTO profile (id, name, preset, crf, input_path, output_path,  is_live_recordings, process_modified_older_than, assemble_after_time, is_active) VALUES
                      (1, \'recordings\', 8, 40, \'recordings\', \'done\', false, 0, 0, true)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE media_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE media_file_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE profile_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE project_id_seq CASCADE');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10C166D1F9C');
        $this->addSql('ALTER TABLE media_file DROP CONSTRAINT FK_4FD8E9C3EA9FDD75');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EECCFA12B8');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE media_file');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE project');
    }
}
