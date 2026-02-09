# Frontend - Gestión Voluntariado

Aplicación Web (Cliente) desarrollada en **Angular** para la gestión de voluntarios y organizaciones.

## Requisitos Previos

*   **Node.js**: Versión LTS recomendada v18+.
*   **Angular CLI**: Instalado globalmente (`npm install -g @angular/cli`).

## Instalación

1.  Entra en la carpeta del frontend:
    ```bash
    cd GestionVoluntariado
    ```
2.  Instala las dependencias:
    ```bash
    ```bash
    npm install --legacy-peer-deps
    ```
    > **Importante**: Usamos `--legacy-peer-deps` debido a conflictos de versiones en las dependencias.

## Desarrollo Local

Para iniciar el servidor de desarrollo:

```bash
ng serve
```

La aplicación estará disponible en `http://localhost:4200/`.

## Estructura Clave

*   `src/app/core`: Servicios singleton, guardias de autenticación e interceptores.
*   `src/app/shared`: Componentes reutilizables (Botones, Alertas, UI Kits).
*   `src/app/modules`: Módulos funcionales (Auth, Voluntario, Organización).
*   `src/environments`: Configuración de entorno (Firebase, API URL).

## Troubleshooting

### Error de dependencias
Si `npm install` falla, prueba una limpieza completa y reinstalación:

```bash
# Windows
rmdir /s /q node_modules
del package-lock.json

npm install --legacy-peer-deps
```

### Problemas de CORS

Durante el desarrollo local (`ng serve`), el frontend corre en `http://localhost:4200` y el backend en `http://localhost:8000`, lo que genera bloqueos por CORS (Cross-Origin Resource Sharing).

---

#### ✅ Solución Actual (Solo Desarrollo)

Usamos un **proxy interno de Angular** configurado en `proxy.conf.json`:
- Todas las peticiones a `/api` se redirigen a `http://127.0.0.1:8000`
- El navegador cree que frontend y backend están en el mismo origen

> ⚠️ **IMPORTANTE**: Esta solución **SOLO funciona con `ng serve`** y **NO está disponible en producción**.

---

#### 🚀 Soluciones para Producción

El proxy de Angular NO funciona en builds de producción. Hay dos opciones viables:

**Opción 1: Mismo Origen con Nginx (Recomendado)**
- Usar Nginx como reverse proxy para servir frontend y backend bajo el mismo dominio
- Configuración:
  - Frontend: `https://miweb.com/`
  - Backend API: `https://miweb.com/api`
- **Ventaja**: Elimina completamente los problemas de CORS
- **Estado actual**: Implementado en `docker-compose.yml` con servicio `backend-web`

**Opción 2: Habilitar CORS en Backend (No Recomendado)**
- Configurar Symfony con `NelmioCorsBundle` para aceptar peticiones del dominio del frontend
- **Desventaja**: Requiere configuración adicional de seguridad y puede tener problemas de rendimiento
- **Uso**: Solo si no se puede usar Nginx/Apache

---

#### 📋 Configuración Actual

**Desarrollo Local:**
```bash
ng serve  # Usa proxy.conf.json automáticamente
```

**Producción (Docker):**
```bash
docker compose up -d  # Nginx maneja CORS automáticamente
```

El frontend compilado se sirve desde Nginx, y las peticiones a `/api` se redirigen al backend PHP-FPM.


