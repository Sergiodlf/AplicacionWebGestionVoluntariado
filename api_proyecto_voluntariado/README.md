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



---

---

## 4. Configuración del Entorno (.env)

El archivo `.env` incluido en el repositorio tiene valores por defecto seguros. Para configurar tu entorno local (base de datos, correo, etc.) **NO edites el archivo `.env` directamente**.

1. Crea un archivo llamado `.env.local` en la raíz de `api_proyecto_voluntariado/`.
2. Sobrescribe las variables que necesites. Este archivo es ignorado por Git, así que puedes poner tus contraseñas reales.

Ejemplo de contenido para `.env.local`:

```bash
# Configuración Real de Base de Datos
DATABASE_URL="mysql://root:tu_password@127.0.0.1:3306/nombre_bbdd?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

# Configuración de Correo (Cuenta Compartida)
# Usar el correo del proyecto. ¡Pedir contraseña por el grupo de WhatsApp/Discord!
MAILER_DSN=gmail://notificaciones4v@gmail.com:LA_CONTRASEÑA_DEL_GRUPO@default
```

---

## 5. Base de Datos

1. Asegúrate de que MySQL está corriendo (XAMPP).
2. Ten configurada tu `DATABASE_URL` en el `.env.local` (ver paso anterior).
3. Crea la base de datos y las tablas:
   ```bash
   php bin/console doctrine:database:create
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


---

## 8. Arquitectura y Estándares (Guía para Desarrolladores)

Este proyecto sigue una arquitectura por capas estricta para mantener el código ordenado, testable y mantenible.

### 8.1. Patrón Controller-Service-Repository

Cada componente tiene una responsabilidad única:

1.  **Controller (`src/Controller`)**:
    -   **Responsabilidad**: Entrada/Salida (HTTP).
    -   **QUÉ HACE**: Recibe la `Request`, valida inputs básicos (o usa DTOs), llama al `Service` y devuelve una `JsonResponse`.
    -   **QUÉ NO HACE**: **NUNCA** accede a la Base de Datos directamente (`EntityManager`, `Repository`). No contiene lógica de negocio compleja.

2.  **Service (`src/Service`)**:
    -   **Responsabilidad**: Lógica de Negocio.
    -   **QUÉ HACE**: Orquesta todo. Llama a Repositorios para buscar datos, valida reglas de negocio (ej: "¿hay cupo?", "¿es voluntario?"), realiza cálculos y persiste cambios (`flush`).
    -   **QUÉ NO HACE**: No maneja objetos HTTP (`Request`/`Response`). Devuelve Entidades, DTOs o booleanos.

3.  **Repository (`src/Repository`)**:
    -   **Responsabilidad**: Acceso a Datos (Query Logic).
    -   **QUÉ HACE**: Contiene métodos para buscar datos específicos (ej: `findActiveInscriptions()`, `findByCif()`).
    -   **QUÉ NO HACE**: No toma decisiones de negocio. Solo devuelve datos.

### 8.2. Uso de DTOs y Enums

-   **DTOs (Data Transfer Objects)**: Se usan para recibir datos del Frontend (POST/PUT) de forma tipada, evitando arrays asociativos mágicos (`$data['campo']`).
-   **Enums (PHP 8.1+)**: Se usan para todos los estados y tipos finitos (`InscriptionStatus`, `UserRole`, `ActivityStatus`). **Está prohibido usar "strings mágicos"** para comparar estados.

### 8.3. Console Commands (`src/Command`)

Usamos Comandos de Consola de Symfony para tareas que no deben ser expuestas vía API REST o que requieren ejecución en segundo plano/cron.

-   **Ubicación**: `src/Command`
-   **Ejecución**: `php bin/console app:nombre-comando`
-   **Casos de Uso**:
    -   **Mantenimiento**: Corregir datos inconsistentes (ej: `app:fix-habilidades`).
    -   **Tareas Programadas**: Verificar estados, enviar recordatorios masivos.
    -   **Administración**: Crear usuarios admin iniciales (`app:create-admin`).
    -   **Migración de Datos**: Mover datos de JSON a BD.

> **Regla de Oro**: Si una operación tarda más de 2 segundos o es una tarea administrativa crítica, considera moverla a un Command en lugar de un Controller.
