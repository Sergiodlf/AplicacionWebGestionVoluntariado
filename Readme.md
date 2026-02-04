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

### [Frontend (Angular App)](GestionVoluntariado/README.md)
*   **Tecnología**: Angular 17+, TypeScript.
*   **Contenido**: Interfaz de usuario para Voluntarios y Organizaciones.
*   **[Ver Guía de Instalación Frontend](GestionVoluntariado/README.md)**

### [Mobile App (Android)](https://github.com/Gari885/AplicacionMovilGestionVoluntariado)
*   **Repositorio Externo**: [Gari885/AplicacionMovilGestionVoluntariado](https://github.com/Gari885/AplicacionMovilGestionVoluntariado)
*   **Tecnología**: Android Nativo.
*   **Contenido**: Cliente móvil para Voluntarios.

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
Puedes generar estos usuarios (verificados y listos para usar) ejecutando en el backend:
```bash
php bin/console app:create-test-users
```

| Rol | Email | Contraseña | Estado |
| :--- | :--- | :--- | :--- |
| **Voluntario** | `voluntario_test@curso.com` | `123456` | Verificado |
| **Organización** | `organizacion_test@curso.com` | `123456` | Verificado |
| **Admin** | `admin@admin.com` | `123456` | (Legacy) |

> **Nota:** Estos usuarios tienen el email verificado para poder hacer login inmediatamente sin necesidad de enviar correos reales.

---

## Flujo de Trabajo (Git Strategy)

Implementamos una estrategia de **Git Flow** simplificada.

*   **`main`**: Código productivo. Solo merges de versiones estables.
*   **`develop`**: Rama de integración.
*   **`feature/` / `fix/`**: Ramas para nuevas funcionalidades o correcciones.


