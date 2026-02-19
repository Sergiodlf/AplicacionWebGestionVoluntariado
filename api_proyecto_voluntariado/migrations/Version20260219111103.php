<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219111103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE administrador');
        $this->addSql('ALTER TABLE ACTIVIDADES DROP COLUMN ESTADO_NEW');
        $this->addSql('ALTER TABLE ACTIVIDADES DROP CONSTRAINT [DF__ACTIVIDAD__ESTAD__4BAC3F29]');
        $this->addSql('ALTER TABLE INSCRIPCIONES ADD FECHA_INSCRIPCION DATETIME2(6)');
        $this->addSql('ALTER TABLE ORGANIZACIONES ALTER COLUMN CP CHAR(5) NOT NULL');
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
        $this->addSql('CREATE TABLE administrador (id INT IDENTITY NOT NULL, email NVARCHAR(180) COLLATE Modern_Spanish_CI_AS NOT NULL, roles VARCHAR(MAX) COLLATE Modern_Spanish_CI_AS NOT NULL, nombre NVARCHAR(255) COLLATE Modern_Spanish_CI_AS NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE NONCLUSTERED INDEX UNIQ_44F9A521E7927C74 ON administrador (email) WHERE email IS NOT NULL');
        $this->addSql('ALTER TABLE ACTIVIDADES ADD ESTADO_NEW NVARCHAR(255)');
        $this->addSql('ALTER TABLE ACTIVIDADES ADD DEFAULT \'PENDIENTE\' FOR ESTADO_APROBACION');
        $this->addSql('ALTER TABLE INSCRIPCIONES DROP COLUMN FECHA_INSCRIPCION');
        $this->addSql('ALTER TABLE ORGANIZACIONES ALTER COLUMN CP NCHAR(5) NOT NULL');
    }
}
