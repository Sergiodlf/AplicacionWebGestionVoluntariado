# API Proyecto Voluntariado

Guía completa para poner en marcha el Backend (API Symfony).

## 1. Requisitos Previos

### Entorno
- **PHP**: Versión 8.2 o superior.
- **Composer**: Gestor de dependencias de PHP.
- **XAMPP/MySQL**: Para la base de datos local.

### Configuración PHP (Sodium)
Este proyecto usa librerías de encriptación que requieren la extensión `sodium`.

1. Abre tu `php.ini` (en XAMPP suele estar en `C:\xampp\php\php.ini`).
2. Busca `;extension=sodium`.
3. **Descoméntala** (quita el `;`):
   ```ini
   extension=sodium
   ```
4. Guarda y reinicia Apache si lo tienes abierto.

---

## 2. Instalación

Clona el repositorio y entra en la carpeta de la API (`api_proyecto_voluntariado`):

```bash
composer install
```

Si da error de extensiones, asegúrate de haber completado el paso 1.

---

## 3. Configuración de Secretos (IMPORTANTE)

Para que funcione la autenticación con Firebase (Login, Registros, Notificaciones), necesitas el archivo de credenciales de servicio.

1. Consigue el archivo `firebase_service_account.json` (pídelo al administrador del proyecto o descárgalo de la consola de Firebase: *Project Settings > Service Accounts > Generate New Private Key*).
2. Colócalo en la siguiente ruta exacta:
   
   `config/secrets/firebase_service_account.json`

> **Nota**: Al ser un proyecto escolar/demo, este archivo **SE INCLUYE** en el repositorio para facilitar la instalación. En un entorno de producción real, debería ignorarse.

---

## 4. Base de Datos

1. Asegúrate de que MySQL está corriendo (XAMPP).
2. Configura tu conexión en el archivo `.env`:
   ```bash
   DATABASE_URL="mysql://root:@127.0.0.1:3306/nombre_bbdd?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
   ```
3. Crea la base de datos y las tablas:
   ```bash
   php bin/console doctrine:database:create
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```
4. Cargar datos de prueba (opcional)
   ```bash
   php bin/console doctrine:fixtures:load
   ``` 

---

## 5. Iniciar Servidor

Para iniciar el servidor de desarrollo de Symfony:

```bash
symfony server:start
```
O usando PHP directamente:
```bash
php -S 127.0.0.1:8000 -t public
```

La API estará disponible en: `http://127.0.0.1:8000/api`
