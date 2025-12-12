<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211083723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ACTIVIDADES (cod_actividad SMALLINT IDENTITY NOT NULL, nombre NVARCHAR(40) NOT NULL, estado NVARCHAR(40) NOT NULL, descripcion VARCHAR(MAX), fecha_inicio DATETIME2(6) NOT NULL, fecha_fin DATETIME2(6) NOT NULL, max_participantes SMALLINT NOT NULL, ods VARCHAR(MAX), cif_organizacion NCHAR(9), PRIMARY KEY (cod_actividad))');
        $this->addSql('CREATE INDEX IDX_FED6DA36E56ECC42 ON ACTIVIDADES (cif_organizacion)');
        $this->addSql('CREATE TABLE CICLOS (CURSO SMALLINT NOT NULL, NOMBRE NVARCHAR(40) NOT NULL, PRIMARY KEY (CURSO, NOMBRE))');
        $this->addSql('CREATE TABLE ORGANIZACIONES (cif NCHAR(9) NOT NULL, nombre NVARCHAR(40) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, sector NVARCHAR(100), direccion NVARCHAR(40) NOT NULL, localidad NVARCHAR(40) NOT NULL, descripcion NVARCHAR(200) NOT NULL, ESTADO NVARCHAR(20) NOT NULL, PRIMARY KEY (cif))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_32952273E7927C74 ON ORGANIZACIONES (email) WHERE email IS NOT NULL');
        $this->addSql('ALTER TABLE ORGANIZACIONES ADD DEFAULT \'Pendiente\' FOR ESTADO');
        $this->addSql('CREATE TABLE VOLUNTARIOS (DNI NCHAR(9) NOT NULL, NOMBRE NVARCHAR(40) NOT NULL, APELLIDO1 NVARCHAR(40) NOT NULL, APELLIDO2 NVARCHAR(40) NOT NULL, CORREO NVARCHAR(40) NOT NULL, PASSWORD VARCHAR(255) NOT NULL, ZONA NVARCHAR(100), FECHA_NACIMIENTO DATETIME2(6) NOT NULL, EXPERIENCIA NVARCHAR(200), COCHE BIT NOT NULL, HABILIDADES VARCHAR(MAX), INTERESES VARCHAR(MAX), IDIOMAS VARCHAR(MAX), CURSO_CICLOS SMALLINT, NOMBRE_CICLOS NVARCHAR(40), PRIMARY KEY (DNI))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CC29E0C987FCCC4F ON VOLUNTARIOS (CORREO) WHERE CORREO IS NOT NULL');
        $this->addSql('CREATE INDEX IDX_CC29E0C94F5222D9CCB2DE43 ON VOLUNTARIOS (CURSO_CICLOS, NOMBRE_CICLOS)');
        $this->addSql('CREATE TABLE VOLUNTARIOS_ACTIVIDADES (voluntario_dni NCHAR(9) NOT NULL, actividad_cod SMALLINT NOT NULL, PRIMARY KEY (voluntario_dni, actividad_cod))');
        $this->addSql('CREATE INDEX IDX_A7E8F1A86A56CB89 ON VOLUNTARIOS_ACTIVIDADES (voluntario_dni)');
        $this->addSql('CREATE INDEX IDX_A7E8F1A8D6F2985 ON VOLUNTARIOS_ACTIVIDADES (actividad_cod)');
        $this->addSql('ALTER TABLE ACTIVIDADES ADD CONSTRAINT FK_FED6DA36E56ECC42 FOREIGN KEY (cif_organizacion) REFERENCES ORGANIZACIONES (cif)');
        $this->addSql('ALTER TABLE VOLUNTARIOS ADD CONSTRAINT FK_CC29E0C94F5222D9CCB2DE43 FOREIGN KEY (CURSO_CICLOS, NOMBRE_CICLOS) REFERENCES CICLOS (CURSO, NOMBRE)');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES ADD CONSTRAINT FK_A7E8F1A86A56CB89 FOREIGN KEY (voluntario_dni) REFERENCES VOLUNTARIOS (dni)');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES ADD CONSTRAINT FK_A7E8F1A8D6F2985 FOREIGN KEY (actividad_cod) REFERENCES ACTIVIDADES (cod_actividad)');
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
        $this->addSql('ALTER TABLE VOLUNTARIOS DROP CONSTRAINT FK_CC29E0C94F5222D9CCB2DE43');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES DROP CONSTRAINT FK_A7E8F1A86A56CB89');
        $this->addSql('ALTER TABLE VOLUNTARIOS_ACTIVIDADES DROP CONSTRAINT FK_A7E8F1A8D6F2985');
        $this->addSql('DROP TABLE ACTIVIDADES');
        $this->addSql('DROP TABLE CICLOS');
        $this->addSql('DROP TABLE ORGANIZACIONES');
        $this->addSql('DROP TABLE VOLUNTARIOS');
        $this->addSql('DROP TABLE VOLUNTARIOS_ACTIVIDADES');
    }
}
