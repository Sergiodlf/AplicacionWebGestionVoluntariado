<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128022304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ACTIVIDADES (cod_actividad INT IDENTITY NOT NULL, nombre NVARCHAR(255) NOT NULL, estado NVARCHAR(50) NOT NULL, descripcion VARCHAR(MAX), fecha_inicio DATE, max_participantes INT, ods VARCHAR(MAX), cif_organizacion NVARCHAR(20) NOT NULL, PRIMARY KEY (cod_actividad))');
        $this->addSql('CREATE INDEX IDX_FED6DA36E56ECC42 ON ACTIVIDADES (cif_organizacion)');
        $this->addSql('CREATE TABLE ORGANIZACIONES (cif NVARCHAR(20) NOT NULL, nombre NVARCHAR(255) NOT NULL, email NVARCHAR(180) NOT NULL, password NVARCHAR(255) NOT NULL, sector NVARCHAR(100), direccion NVARCHAR(255), localidad NVARCHAR(100), descripcion VARCHAR(MAX), PRIMARY KEY (cif))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_32952273E7927C74 ON ORGANIZACIONES (email) WHERE email IS NOT NULL');
        $this->addSql('CREATE TABLE VOLUNTARIOS (dni NVARCHAR(20) NOT NULL, nombre NVARCHAR(50) NOT NULL, apellido1 NVARCHAR(50), apellido2 NVARCHAR(50), correo NVARCHAR(180) NOT NULL, password NVARCHAR(255) NOT NULL, zona NVARCHAR(100), fecha_nacimiento DATE, experiencia NVARCHAR(255), coche BIT, curso_ciclos NVARCHAR(100), habilidades VARCHAR(MAX), intereses VARCHAR(MAX), idiomas VARCHAR(MAX), PRIMARY KEY (dni))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CC29E0C977040BC9 ON VOLUNTARIOS (correo) WHERE correo IS NOT NULL');
        $this->addSql('ALTER TABLE ACTIVIDADES ADD CONSTRAINT FK_FED6DA36E56ECC42 FOREIGN KEY (cif_organizacion) REFERENCES ORGANIZACIONES (cif)');
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
        $this->addSql('ALTER TABLE ACTIVIDADES DROP CONSTRAINT FK_FED6DA36E56ECC42');
        $this->addSql('DROP TABLE ACTIVIDADES');
        $this->addSql('DROP TABLE ORGANIZACIONES');
        $this->addSql('DROP TABLE VOLUNTARIOS');
    }
}
