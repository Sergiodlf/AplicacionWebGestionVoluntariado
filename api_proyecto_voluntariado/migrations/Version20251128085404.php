<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128085404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE VOLUNTARIOS_ACTIVIDADES (cod_actividad INT NOT NULL, dni_voluntario NVARCHAR(20) NOT NULL, PRIMARY KEY (cod_actividad, dni_voluntario))');
        $this->addSql('CREATE INDEX IDX_A7E8F1A8256292BC ON VOLUNTARIOS_ACTIVIDADES (cod_actividad)');
        $this->addSql('CREATE INDEX IDX_A7E8F1A82C8A89E8 ON VOLUNTARIOS_ACTIVIDADES (dni_voluntario)');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES ADD CONSTRAINT FK_A7E8F1A8256292BC FOREIGN KEY (cod_actividad) REFERENCES ACTIVIDADES (cod_actividad)');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES ADD CONSTRAINT FK_A7E8F1A82C8A89E8 FOREIGN KEY (dni_voluntario) REFERENCES VOLUNTARIOS (dni)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA db_accessadmin');
        $this->addSql('CREATE SCHEMA db_backupoperator');
        $this->addSql('CREATE SCHEMA db_datareader');
        $this->addSql('CREATE SCHEMA db_datawriter');
        $this->addSql('CREATE SCHEMA db_ddladmin');
        $this->addSql('CREATE SCHEMA db_denydatareader');
        $this->addSql('CREATE SCHEMA db_denydatawriter');
        $this->addSql('CREATE SCHEMA db_owner');
        $this->addSql('CREATE SCHEMA db_securityadmin');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES DROP CONSTRAINT FK_A7E8F1A8256292BC');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES DROP CONSTRAINT FK_A7E8F1A82C8A89E8');
        $this->addSql('DROP TABLE VOLUNTARIOS_ACTIVIDADES');
    }
}
