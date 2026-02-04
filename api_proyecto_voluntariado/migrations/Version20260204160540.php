<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204160540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ACTIVIDADES ADD SECTOR NVARCHAR(50)');
        $this->addSql('ALTER TABLE ACTIVIDADES ADD DESCRIPCION VARCHAR(MAX)');
        
        // Robustly drop the unique constraint (names are dynamic in SQL Server)
        $this->addSql("DECLARE @constraint NVARCHAR(255) = (SELECT name FROM sys.objects WHERE type_desc = 'UNIQUE_CONSTRAINT' AND parent_object_id = OBJECT_ID('ORGANIZACIONES') AND name LIKE 'UQ__ORGANIZA%'); IF @constraint IS NOT NULL EXEC('ALTER TABLE ORGANIZACIONES DROP CONSTRAINT ' + @constraint)");

        $this->addSql('ALTER TABLE ORGANIZACIONES ADD FCM_TOKEN NVARCHAR(255)');
        $this->addSql('ALTER TABLE ORGANIZACIONES ALTER COLUMN CP CHAR(5) NOT NULL');
        $this->addSql('ALTER TABLE VOLUNTARIOS ADD FCM_TOKEN NVARCHAR(255)');
        $this->addSql('ALTER TABLE administrador DROP COLUMN password');
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
        $this->addSql('ALTER TABLE ACTIVIDADES DROP COLUMN SECTOR');
        $this->addSql('ALTER TABLE ACTIVIDADES DROP COLUMN DESCRIPCION');
        $this->addSql('ALTER TABLE administrador ADD password NVARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ORGANIZACIONES DROP COLUMN FCM_TOKEN');
        $this->addSql('ALTER TABLE ORGANIZACIONES ALTER COLUMN CP NCHAR(5) NOT NULL');
        $this->addSql('CREATE UNIQUE NONCLUSTERED INDEX UQ__ORGANIZA__161CF724435A488C ON ORGANIZACIONES (EMAIL) WHERE EMAIL IS NOT NULL');
        $this->addSql('ALTER TABLE VOLUNTARIOS DROP COLUMN FCM_TOKEN');
    }
}
