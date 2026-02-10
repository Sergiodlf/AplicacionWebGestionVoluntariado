# Guía de Seguridad - Configuración de Secrets

## Variables de Entorno y Secrets

Este documento describe cómo configurar correctamente las variables de entorno y secrets del proyecto en diferentes entornos.

##  Desarrollo Local

### 1. Configurar Variables de Entorno

Copia el archivo de ejemplo y configura tus valores:

```bash
cd api_proyecto_voluntariado
cp .env.example .env.local
```

Edita `.env.local` con tus valores reales:

```env
# Generar APP_SECRET (elige uno):
# Opción 1 - Usando Symfony CLI:
php bin/console secrets:generate-keys

# Opción 2 - Usando OpenSSL:
openssl rand -base64 32

# Opción 3 - Online: https://www.random.org/strings/

APP_SECRET=tu_secret_generado_aqui

# Base de datos local
DATABASE_URL="sqlsrv://sa:TuPassword@127.0.0.1:1433/PROYECTOINTER?"

# Firebase
FIREBASE_API_KEY=tu_firebase_api_key
```

### 2. Obtener Credenciales de Firebase

1. Ve a [Firebase Console](https://console.firebase.google.com)
2. Selecciona tu proyecto
3. **Web API Key**:
   - Ve a **Project Settings** > **General**
   - Copia el **Web API Key**
4. **Service Account**:
   - Ve a **Project Settings** > **Service Accounts**
   - Click en **Generate New Private Key**
   - Guarda el archivo como `config/secrets/firebase_service_account.json`

---

## Docker (Desarrollo/Testing)

### 1. Configurar Variables Docker

Copia el archivo de ejemplo:

```bash
cp .env.docker.example .env.docker
```

Edita `.env.docker`:

```env
APP_SECRET=GENERA_UNO_NUEVO_AQUI
MSSQL_SA_PASSWORD=TuPasswordSegura123!
FIREBASE_API_KEY=tu_firebase_api_key
```

### 2. Iniciar con Docker Compose

```bash
docker compose up -d --build
```

Docker Compose leerá automáticamente `.env.docker` para las variables de entorno.

---

##  GitHub Secrets (CI/CD)

Si estás configurando GitHub Actions para CI/CD, necesitas añadir secrets al repositorio.

### Configurar Secrets en GitHub

1. Ve a tu repositorio en GitHub
2. **Settings** > **Secrets and variables** > **Actions**
3. Click en **New repository secret**
4. Añade los siguientes secrets:

| Secret Name | Descripción | Cómo Obtenerlo |
|-------------|-------------|----------------|
| `APP_SECRET` | Symfony application secret | `openssl rand -base64 32` |
| `MSSQL_SA_PASSWORD` | Contraseña de SQL Server | Debe cumplir requisitos de complejidad |
| `FIREBASE_API_KEY` | Firebase Web API Key | Firebase Console > Project Settings |
| `FIREBASE_SERVICE_ACCOUNT` | JSON completo del service account | Firebase Console > Service Accounts > Generate Key |
| `DATABASE_URL` | Connection string completa | `sqlsrv://sa:PASSWORD@HOST:1433/DB?` |

### Ejemplo de Uso en GitHub Actions

```yaml
name: CI/CD Pipeline

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    env:
      APP_SECRET: ${{ secrets.APP_SECRET }}
      DATABASE_URL: ${{ secrets.DATABASE_URL }}
      FIREBASE_API_KEY: ${{ secrets.FIREBASE_API_KEY }}
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests
        run: php bin/phpunit
```

---

##  Buenas Prácticas de Seguridad

###  SÍ Hacer

-  Usar `.env.local` para desarrollo (nunca commitear)
-  Generar `APP_SECRET` aleatorios y únicos
-  Usar contraseñas complejas para BD (mín. 8 caracteres, mayúsculas, minúsculas, números, símbolos)
-  Añadir `.env.local` y `.env.docker` a `.gitignore`
-  Rotar secrets regularmente
-  Usar GitHub Secrets para CI/CD

###  NO Hacer

-  **NUNCA** commitear archivos con credenciales reales (`.env.local`, `.env.docker`)
-  **NUNCA** compartir `APP_SECRET` o contraseñas por chat/email
-  **NUNCA** usar contraseñas simples como "password123"
-  **NUNCA** reutilizar secrets entre entornos (dev, staging, prod)
-  **NUNCA** commitear `firebase_service_account.json`

---

##  Verificar Seguridad

### Comprobar que no hay secrets en el repositorio

```bash
# Buscar posibles credenciales expuestas
git grep -i "password"
git grep -i "api_key"
git grep -i "secret"

# Verificar .gitignore
cat .gitignore | grep -E "\.env"
```

Deberías ver solo referencias en archivos `.example` o en documentación.

### Auditar variables de entorno

```bash
# Ver qué variables está usando Symfony
php bin/console about

# Verificar conexión a BD sin exponer credenciales
php bin/console doctrine:query:sql "SELECT 1"
```

---

## Referencias

- [Symfony Secrets Management](https://symfony.com/doc/current/configuration/secrets.html)
- [Docker Secrets](https://docs.docker.com/engine/swarm/secrets/)
- [GitHub Actions Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [OWASP Secrets Management](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html)
