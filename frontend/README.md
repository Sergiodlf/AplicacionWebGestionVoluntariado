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
Actualmente en desarrollo (`ng serve`), las peticiones a la API pueden sufrir bloqueos por CORS (Cross-Origin Resource Sharing) ya que el frontend corre en el puerto `4200` y el backend en el `8000`.

**Solución Actual (Desarrollo):**
Usamos un proxy interno de Angular configurado en `proxy.conf.json`.
- Todas las peticiones a `/api` se redirigen automáticamente a `http://127.0.0.1:8000`.
- Esto "engaña" al navegador haciéndole creer que frontend y backend están en el mismo origen.

**Opciones a Futuro (Producción):**
El proxy de Angular **NO** funciona en producción. Para el despliegue real, se debe optar por una de estas estrategias:
1.  **Habilitar CORS en el Backend:** Configurar Symfony (por ejemplo usando `NelmioCorsBundle`) para que acepte explícitamente peticiones desde el dominio del frontend.
2.  **Servir desde el mismo Origen (Recomendado):** Configurar el servidor web (Nginx/Apache) para que sirva tanto los ficheros estáticos de Angular como la API bajo el mismo dominio (ej: `miweb.com` y `miweb.com/api`). Esto elimina completamente la necesidad de CORS.
