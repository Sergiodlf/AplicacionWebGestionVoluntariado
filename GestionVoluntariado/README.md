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

### Problemas de CORS
Si la API está en otro puerto, asegúrate de que el backend permite peticiones desde `localhost:4200` o usa el `proxy.conf.json`.
