# Sistema de Gestión de Voluntariado (Backend API)

![Branching](https://img.shields.io/badge/gitflow-branching-orange)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.2-777bb4?logo=php)

## Objetivo del Proyecto
El objetivo central es proporcionar una API REST robusta y segura que actúe como motor para una plataforma de voluntariado multicanal (Web y Móvil). El sistema orquesta la relación entre organizaciones que publican causas sociales y voluntarios dispuestos a participar, garantizando la integridad de los datos en procesos críticos como el **Match** y el **Control de Inscripciones**.

---

## Funcionalidades Core (MVP)

1. Gestión de Identidad (Auth):** Registro y Login diferenciado por roles (`Voluntario` y `Organización`) con autenticación segura.
2. **Ciclo de Actividades:** Publicación de ofertas por parte de organizaciones con validación de metadatos (fechas, cupos, ODS).
3. **Motor de Inscripción:** Sistema de registro de voluntarios en actividades con validaciones de negocio en tiempo real (prevención de duplicados y control de aforo).
4. **Sistema de Match:** Lógica para la aprobación y confirmación de plazas entre el voluntario y la entidad.

---

## Flujo de Trabajo y Aportaciones (Git Strategy)

Para mantener la estabilidad del código en el Sprint 4, implementamos una estrategia de **Git Flow** simplificada.

### 1. Modelo de Ramas


[Image of Git Flow branching model]

* **`main`**: Código productivo. Solo se toca mediante merges de versiones estables.
* **`develop`**: Rama de integración. Aquí se fusionan todas las tareas terminadas.
* **`feature/` / `fix/`**: Ramas efímeras. Cada ID de Jira (ej. `PV-36`) debe tener su propia rama.

### 2. Convención de Commits
Utilizamos mensajes semánticos para automatizar el historial de cambios:
- `feat: [ID] descripción` (Nueva funcionalidad)
- `fix: [ID] corrección de bug` (Corrección)
- `refactor: [ID] mejora de código` (Sin cambio funcional)

### 3. Sincronización Preventiva
Antes de enviar un Pull Request, es **obligatorio** sincronizar con la base para evitar conflictos en el servidor:
```bash
git pull --rebase origin develop
```
### Guía de Inicio Rápido

### Requisitos
* **PHP 8.2+** & **Composer**
* **SQL Server** / **MySQL**
* **Symfony CLI** (Recomendado)

### Instalación 

 **1. Clonar y entrar:**
   ```bash
   git clone <url-repo> && cd volunteer-api
   ```

### Instalación de librerías de PHP
```bash
composer install
 ``` 
### Dependencias y Entorno:

### Creación del archivo de configuración local
```bash
cp .env .env.local
 ``` 
#### Nota: Debes configurar tu DATABASE_URL dentro de .env.local

## Persistencia y Base de Datos:

### Crear la base de datos definida en el .env
```bash
php bin/console doctrine:database:create
``` 
### Ejecutar las migraciones para generar las tablas
```bash
php bin/console doctrine:migrations:migrate
``` 

