# Frontend - Gestión Voluntariado

Aplicación Web (Cliente) desarrollada en **Angular** para la gestión de voluntarios y organizaciones.

## Requisitos Previos

*   **Node.js**: Versión LTS recomendada v18+.
*   **Angular CLI**: Instalado globalmente (`npm install -g @angular/cli`).

## Instalación

1.  Entra en la carpeta del frontend:
    ```bash
    cd frontend
    ```
2.  Instala las dependencias:
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

Durante el desarrollo local (`ng serve`), el frontend corre en `http://localhost:4200` y el backend en `http://localhost:8000`, lo que podría generar bloqueos por CORS (Cross-Origin Resource Sharing).

---

#### ✅ Solución Actual: NelmioCorsBundle (Backend)

El backend ahora usa **NelmioCorsBundle** para manejar CORS directamente:
- CORS se configura en el servidor (donde debe estar)
- Funciona en desarrollo Y producción
- No requiere proxy de Angular
- Configuración centralizada en el backend

**Configuración del backend:**
```env
# api_proyecto_voluntariado/.env
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

Esto permite peticiones desde:
- `http://localhost:4200` (Angular dev)
- `http://127.0.0.1:4200`
- Cualquier puerto en localhost

---

#### 🔧 Proxy de Angular (Opcional)

El archivo `proxy.conf.json` sigue disponible como alternativa para desarrollo local, pero **ya no es necesario**.

**Para usar el proxy** (opcional):
```bash
ng serve --proxy-config proxy.conf.json
```

**Sin proxy** (recomendado):
```bash
ng serve
```

El backend maneja CORS automáticamente con NelmioCorsBundle.

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


