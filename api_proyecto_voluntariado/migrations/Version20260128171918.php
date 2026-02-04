<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128171918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('DROP INDEX UQ__ORGANIZA__161CF7241D1A2405 ON ORGANIZACIONES');
        // $this->addSql('ALTER TABLE ORGANIZACIONES ADD FCM_TOKEN NVARCHAR(255)');
        $this->addSql('ALTER TABLE ORGANIZACIONES ALTER COLUMN CP CHAR(5) NOT NULL');
        // $this->addSql('ALTER TABLE VOLUNTARIOS ADD FCM_TOKEN NVARCHAR(255)');
        $this->addSql('ALTER TABLE VOLUNTARIOS ALTER COLUMN NOMBRE NVARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE VOLUNTARIOS ALTER COLUMN APELLIDO1 NVARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE VOLUNTARIOS ALTER COLUMN APELLIDO2 NVARCHAR(100) NOT NULL');
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
        $this->addSql('ALTER TABLE ORGANIZACIONES DROP COLUMN FCM_TOKEN');
        $this->addSql('ALTER TABLE ORGANIZACIONES ALTER COLUMN CP NCHAR(5) NOT NULL');
        $this->addSql('CREATE UNIQUE NONCLUSTERED INDEX UQ__ORGANIZA__161CF7241D1A2405 ON ORGANIZACIONES (EMAIL) WHERE EMAIL IS NOT NULL');
        $this->addSql('ALTER TABLE VOLUNTARIOS DROP COLUMN FCM_TOKEN');
        $this->addSql('ALTER TABLE VOLUNTARIOS ALTER COLUMN NOMBRE NVARCHAR(40) NOT NULL');
        $this->addSql('ALTER TABLE VOLUNTARIOS ALTER COLUMN APELLIDO1 NVARCHAR(40) NOT NULL');
        $this->addSql('ALTER TABLE VOLUNTARIOS ALTER COLUMN APELLIDO2 NVARCHAR(40) NOT NULL');
    }
}
