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
    npm install --legacy-peer-deps
    ```
    > Nota: Usamos `--legacy-peer-deps` para evitar conflictos de versiones con algunas librerías de UI.

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
Si `npm install` falla, prueba borrar `node_modules` y `package-lock.json` y reinstalar.

### Resolución Definitiva de CORS (Estrategia sin Proxy)

**El Problema:**
Al tener el frontend en Angular (puerto `4200`) y el backend en Symfony (puerto `8000` o en la nube), se producen bloqueos de CORS (Cross-Origin Resource Sharing) por seguridad del navegador al ser dominios/puertos distintos. Inicialmente se usaba el `proxy.conf.json` de Angular para enmascararlo, pero esto **no es válido ni escalable para entornos de producción** (AWS, Docker, Nginx).

**La Estrategia a Futuro (Adoptada):**
Se ha decidido **eliminar por completo la dependencia del proxy de Angular (`proxy.conf.json`)**. La comunicación entre frontend y backend será Cross-Origin directa en todos los entornos.

La solución definitiva se divide en dos frentes obligatorios:

1. **En el Backend (Symfony): Configuración de NelmioCorsBundle**
   * Configurar `nelmio/cors-bundle` de manera global.
   * En `nelmio_cors.yaml`, definir la variable de entorno `CORS_ALLOW_ORIGIN` autorizando explícitamente las URLs del frontend (`http://localhost:4200` en desarrollo y el dominio real de AWS en producción).
   * Permitir los métodos HTTP pertinentes (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`) y las cabeceras (`Authorization`, `Content-Type`).

2. **En el Frontend (Angular): Variables de Entorno (`environment`)**
   * Se descartan las peticiones relativas tipo `this.http.get('/api/algo')`.
   * Toda petición a la API debe construirse usando la URL del backend definida en:
     * `environment.development.ts -> apiUrl: 'http://127.0.0.1:8000/api'`
     * `environment.ts (Producción) -> apiUrl: 'https://mi-api-en-aws.com/api'`
   * Ejemplo en servicios: `this.http.get(`${environment.apiUrl}/organizaciones`)`.

**Ventajas de esta Arquitectura:**
* Garantiza una **paridad absoluta** entre los entornos de desarrollo y producción (ambos usan la misma filosofía de conexión).
* Mejora la seguridad, dejando el control estricto de quién accede a los recursos en manos del backend a través de la "Allow-List" de Nelmio.
