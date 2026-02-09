-- Script de inicialización de Base de Datos
-- Gestión Voluntariado

USE master;
GO

-- Crear base de datos si no existe
IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = 'PROYECTOINTER')
BEGIN
    CREATE DATABASE PROYECTOINTER;
    PRINT 'Base de datos PROYECTOINTER creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La base de datos PROYECTOINTER ya existe.';
END
GO

USE PROYECTOINTER;
GO

-- Las tablas serán creadas por Doctrine Migrations
-- Este script solo garantiza que la base de datos existe

PRINT 'Inicialización completada. Ejecutar migraciones de Doctrine para crear tablas.';
GO
