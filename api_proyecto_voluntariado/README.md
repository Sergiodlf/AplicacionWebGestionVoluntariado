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

### 2.1. Instalar Dependencias

Asegúrate de tener `composer` instalado. Si no lo tienes en el PATH de Windows, puedes descargarlo localmente:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

Luego instala las dependencias (si descargaste `composer.phar` usa `php composer.phar install`):

```bash
composer install
# O si usas el .phar local:
php composer.phar install
```

Si da error de extensiones, asegúrate de haber completado el paso 1.

---

## 3. Configuración de Secretos (IMPORTANTE)

Para que funcione la autenticación con Firebase (Login, Registros, Notificaciones), necesitas el archivo de credenciales de servicio.

1. Consigue el archivo `firebase_service_account.json` (pídelo al administrador del proyecto o descárgalo de la consola de Firebase: *Project Settings > Service Accounts > Generate New Private Key*).
2. Colócalo en la siguiente ruta exacta:
   
   `config/secrets/firebase_service_account.json`



---

---

## 4. Configuración del Entorno (.env)

### 🔐 Seguridad de Secrets

> [!IMPORTANT]
> **NUNCA commitees credenciales reales al repositorio.**
> 
> El archivo `.env` contiene valores por defecto seguros y placeholders. Para tu entorno local, usa `.env.local` que está en `.gitignore`.

### Configurar Variables de Entorno

1. **Copia el archivo de ejemplo**:
   ```bash
   cp .env.example .env.local
   ```

2. **Genera un APP_SECRET seguro**:
   ```bash
   # Opción 1: Symfony CLI (recomendado)
   php bin/console secrets:generate-keys
   
   # Opción 2: PowerShell (Windows)
   [Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Minimum 0 -Maximum 256 }))
   
   # Opción 3: Online (solo desarrollo)
   # https://www.random.org/strings/?num=1&len=32&digits=on&upperalpha=on&loweralpha=on
   ```

3. **Edita `.env.local` con tus valores**:

```env
# APP_SECRET generado en el paso anterior
APP_SECRET=tu_secret_aleatorio_aqui

# Configuración de Base de Datos
# Para desarrollo local (XAMPP/MySQL):
DATABASE_URL="mysql://root:tu_password@127.0.0.1:3306/PROYECTOINTER?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

# Para SQL Server local:
DATABASE_URL="sqlsrv://sa:TuPassword@127.0.0.1:1433/PROYECTOINTER?serverVersion=2019&Encrypt=no"

# Configuración de Correo (Cuenta del Proyecto)
MAILER_DSN=gmail://notificaciones4v@gmail.com:PASSWORD_DEL_GRUPO@default

# Firebase (obtener de Firebase Console)
FIREBASE_API_KEY=tu_firebase_web_api_key
```

> [!TIP]
> Lee la [Guía de Seguridad completa](../Seguridad.md) para más detalles sobre gestión de secrets.

---

## 5. Base de Datos

1. Asegúrate de que tu servidor de base de datos (MySQL o SQL Server) esté corriendo.
2. Ten configurada tu `DATABASE_URL` en el `.env.local` (ver paso anterior).
   - **Nota para SQL Server**: Asegúrate de usar el driver `sqlsrv://` y que la extensión esté habilitada en `php.ini`.
3. Crea la base de datos y las tablas:
   ```bash
   # Crear la BBDD (si no existe)
   php bin/console doctrine:database:create --if-not-exists

   # Generar migración inicial (si la carpeta `migrations` está vacía o da error de version "latest")
   php bin/console make:migration

   # Ejecutar migraciones
   php bin/console doctrine:migrations:migrate
   ```
4. Cargar datos de prueba (opcional)
   ```bash
   php bin/console doctrine:fixtures:load
   ```
---

## 6. Iniciar Servidor

Para iniciar el servidor de desarrollo de Symfony:

```bash
symfony server:start
```
O usando PHP directamente:
```bash
php -S 127.0.0.1:8000 -t public
```


---

## 7. Usuarios de Prueba y Verificación

### Arquitectura "Thin Client" y Emails
Este proyecto utiliza una arquitectura donde el Backend gestiona la lógica crítica.
- **Registro**: El backend crea el usuario en Firebase y envía un email de verificación (usando Gmail).
- **Login**: Se requiere que el email esté verificado.
- **Olvido de Contraseña**: Endpoint `/api/auth/forgot-password`.

### Credenciales de Test
Puedes cargar un set completo de datos de prueba sincronizados con Firebase usando:
```bash
php bin/console doctrine:fixtures:load --append
```
*(Usa `--append` si no quieres borrar el resto de tus datos de la base de datos local).*

Esto generará/actualizará:
- **3 usuarios específicos de prueba** (documentados abajo para login fácil)
- 10 voluntarios adicionales con emails como `carlos.lopez0@test.com`
- 5 organizaciones adicionales con emails como `ecovida0@test.com`
- Datos maestros (Habilidades, Intereses, ODS, Ciclos)
- 3 actividades de ejemplo

#### Usuarios específicos para pruebas rápidas

| Rol | Email | Password | Estado |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@curso.com` | `admin123` | Verificado |
| **Voluntario** | `voluntario_test@curso.com` | `123456` | Verificado |
| **Organización** | `organizacion_test@curso.com` | `123456` | Verificado |

*Nota: Estos usuarios tienen `emailVerified: true` en Firebase para poder hacer login inmediatamente.*
