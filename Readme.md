# Sistema de Gestión de Voluntariado

![Branching](https://img.shields.io/badge/gitflow-branching-orange)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.2-777bb4?logo=php)
![Angular](https://img.shields.io/badge/Angular-17-dd0031?logo=angular)

## Objetivo del Proyecto
El objetivo central es desarrollar una aplicación web para la gestión del voluntariado de cuatrovientos. El sistema orquesta la relación entre organizaciones que publican causas sociales y voluntarios dispuestos a participar, garantizando la integridad de los datos en procesos críticos como el **Match** y el **Control de Inscripciones**.

---

## Estructura del Proyecto

Este repositorio contiene tanto el Backend como el Frontend de la aplicación. Para ver las guías de instalación y configuración detalladas de cada parte, por favor consulta sus respectivos READMEs:

### [Backend (API Symfony)](api_proyecto_voluntariado/README.md)
*   **Tecnología**: Symfony 7, PHP 8.2, MySQL.
*   **Contenido**: API REST, gestión de base de datos, autenticación con Firebase, lógica de negocio.
*   **[Ver Guía de Instalación Backend](api_proyecto_voluntariado/README.md)**

### [Frontend (Angular App)](frontend/README.md)
*   **Tecnología**: Angular 17+, TypeScript.
*   **Contenido**: Interfaz de usuario para Voluntarios y Organizaciones.
*   **[Ver Guía de Instalación Frontend](frontend/README.md)**

### [Docker Deployment](DOCKER_DEPLOYMENT.md)
*   **Tecnología**: Docker Compose, SQL Server 2022, Nginx.
*   **Contenido**: Despliegue completo con contenedores (Backend + Frontend + BD).
*   **Levantamiento rápido**:
    ```bash
    # 1. Levantar todos los servicios (Frontend, Backend, BD)
    docker compose up -d --build
    
    # 2. Inicializar la base de datos con datos de prueba
    # Windows PowerShell:
    .\init-database.ps1
    # Linux/Mac:
    ./init-database.sh
    ```
*   **[Ver Guía Completa de Despliegue Docker](DOCKER_DEPLOYMENT.md)**

### [Mobile App (Android)](https://github.com/Gari885/AplicacionMovilGestionVoluntariado)
*   **Repositorio Externo**: [Gari885/AplicacionMovilGestionVoluntariado](https://github.com/Gari885/AplicacionMovilGestionVoluntariado)
*   **Tecnología**: Android Nativo.
*   **Contenido**: Cliente móvil para Voluntarios.

### [Seguridad y Configuración de Secrets](Seguridad.md)
*   **Documentación**: Guía completa de configuración de variables de entorno y secrets.
*   **Contenido**: Configuración local, Docker, GitHub Actions, y buenas prácticas de seguridad.
*   **[Ver Guía de Seguridad](Seguridad.md)**

---

## Funcionalidades Core

1.  **Gestión de Identidad (Auth):**
    *   Registro y Login diferenciado por roles (`Voluntario` y `Organización`) con autenticación segura.
    *   **Perfiles Completos:** Gestión de habilidades, intereses y disponibilidad.

2.  **Ciclo de Actividades:**
    *   Publicación de ofertas con validación de metadatos.
    *   **Filtrado Avanzado:** Búsqueda por Zona, Habilidades, etc.
    *   **Dashboards:** Paneles de control métricas en tiempo real.

3.  **Motor de Inscripción y Matching:**
    *   Estados: `PENDIENTE`, `CONFIRMADO`, `RECHAZADO`, `EN CURSO`, `FINALIZADO`.
    *   **Match Administrativo:** Asignación manual de voluntarios.
    *   **Control de Aforo:** Validaciones de negocio.

4.  **Gestión Administrativa:**
    *   Validación de usuarios.
    *   Supervisión global.

---

## Usuarios de Prueba


### Generación Automática
Puedes generar estos usuarios de dos formas:

**Opción 1: Comando Symfony (Backend local)**
```bash
php bin/console app:create-test-users
```

**Opción 2: Docker Fixtures (Recomendado)**
```powershell
docker compose up -d
.\init-database.ps1
```

| Rol | Email | Contraseña | Estado |
| :--- | :--- | :--- | :--- |
| **Voluntario** | `voluntario_test@curso.com` | `123456` | Verificado |
| **Organización** | `organizacion_test@curso.com` | `123456` | Verificado |
| **Admin** | `admin@curso.com` | `admin123` | Verificado |

> **Nota:** Los fixtures de Docker generan 10 voluntarios adicionales, 5 organizaciones y 3 actividades de prueba. Ver [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md) para más detalles.

---

## Flujo de Trabajo (Git Strategy)

Implementamos una estrategia de **Git Flow** simplificada.

*   **`main`**: Código productivo. Solo merges de versiones estables.
*   **`develop`**: Rama de integración.
*   **`feature/` / `fix/`**: Ramas para nuevas funcionalidades o correcciones.


