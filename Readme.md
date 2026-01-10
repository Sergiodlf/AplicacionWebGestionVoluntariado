# Sistema de Gestión de Voluntariado (Backend API)

![Branching](https://img.shields.io/badge/gitflow-branching-orange)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.2-777bb4?logo=php)

## Objetivo del Proyecto
El objetivo central es proporcionar una API REST robusta y segura que actúe como motor para una plataforma de voluntariado multicanal (Web y Móvil). El sistema orquesta la relación entre organizaciones que publican causas sociales y voluntarios dispuestos a participar, garantizando la integridad de los datos en procesos críticos como el **Match** y el **Control de Inscripciones**.

---

## Funcionalidades Core (Actualizado)

1.  **Gestión de Identidad (Auth):**
    *   Registro y Login diferenciado por roles (`Voluntario` y `Organización`) con autenticación segura y hash de contraseñas.
    *   **Perfiles Completos:** Los voluntarios registran habilidades, intereses, disponibilidad, zona y ciclo formativo. Las organizaciones gestionan su perfil público.

2.  **Ciclo de Actividades:**
    *   Publicación de ofertas por parte de organizaciones con validación de metadatos (fechas, cupos, ODS, habilidades requeridas).
    *   **Filtrado Avanzado:** Los voluntarios pueden buscar actividades por Zona, Habilidades, Disponibilidad, Intereses y Estado.
    *   **Dashboards:** Paneles de control específicos para Administradores, Organizaciones y Voluntarios con métricas en tiempo real.

3.  **Motor de Inscripción y Matching:**
    *   Sistema de registro de voluntarios en actividades con estados: `PENDIENTE`, `CONFIRMADO`, `RECHAZADO`, `EN CURSO`, `FINALIZADO`.
    *   **Match Administrativo:** Los administradores pueden asignar manualmente voluntarios aceptados a actividades.
    *   **Control de Aforo:** Validaciones de negocio para prevenir sobrecupo.

4.  **Gestión Administrativa:**
    *   Validación de nuevos Voluntarios y Organizaciones (Aceptar/Rechazar registros).
    *   Supervisión global de todas las actividades y matches.
    *   **Interfaz Premium:** Diseño moderno con encabezados fijos, tarjetas interactivas y búsqueda optimizada.

---

## Flujo de Trabajo y Aportaciones (Git Strategy)

Para mantener la estabilidad del código, implementamos una estrategia de **Git Flow** simplificada.

### 1. Modelo de Ramas

*   **`main`**: Código productivo. Solo se toca mediante merges de versiones estables.
*   **`develop`**: Rama de integración. Aquí se fusionan todas las tareas terminadas.
*   **`feature/` / `fix/`**: Ramas efímeras para nuevas funcionalidades o correcciones.

### Guías de Instalación

### Requisitos

*   **Backend:** PHP 8.2+, Composer, Symfony CLI, SQL Server / MySQL.
*   **Frontend:** Node.js, Angular CLI.

### Instalación Backend (Symfony)

1.  **Instalar dependencias:**
    ```bash
    composer install
    ```
2.  **Configurar entorno:**
    Copiar `.env` a `.env.local` y configurar `DATABASE_URL`.
3.  **Base de Datos:**
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load  # (Opcional: Cargar datos de prueba)
    ```
4.  **Iniciar Servidor:**
    ```bash
    symfony server:start
    ```

### Instalación Frontend (Angular)

1.  **Instalar dependencias:**
    ```bash
    npm install
    ```
2.  **Iniciar Servidor:**
    ```bash
    ng serve
    ```

