# Guía de Despliegue Docker - Gestión Voluntariado

## Requisitos Previos

- Docker Desktop instalado y corriendo
- Git (para clonar el repositorio)
- Puertos disponibles: `80`, `8000`, `1433`

## Configuración Rápida

### 1. Clonar el Repositorio

```bash
git clone https://github.com/Sergiodlf/AplicacionWebGestionVoluntariado.git
cd AplicacionWebGestionVoluntariado
```

### 2. Configurar Variables de Entorno

**Importante:** Asegúrate de que la contraseña de la base de datos cumpla con los requisitos de SQL Server:
- Mínimo 8 caracteres
- Mayúsculas, minúsculas, números y caracteres especiales

La contraseña actual configurada es: `Volunt@ri0DB2024!`

### 3. Construir y Desplegar

```bash
# Construir todas las imágenes y levantar los servicios
docker compose up -d --build

# Ver el estado de los contenedores
docker compose ps

# Ver logs de un servicio específico
docker compose logs db
docker compose logs backend
docker compose logs frontend
```

## Arquitectura de Servicios

| Servicio | Tecnología | Puerto | Descripción |
|----------|-----------|--------|-------------|
| `db` | SQL Server 2022 | 1433 | Base de datos |
| `backend` | PHP 8.2-FPM + Symfony | Internal (9000) | API Backend |
| `backend-web` | Nginx | 8000 | Servidor web para API |
| `frontend` | Angular 17 + Nginx | 80 | Aplicación web |

## Estructura de Dockerfiles

```
├── database/
│   └── Dockerfile              # SQL Server 2022
├── api_proyecto_voluntariado/
│   ├── Dockerfile              # PHP-FPM + SQL Server drivers
│   └── nginx/
│       ├── Dockerfile          # Nginx para backend
│       └── default.conf        # Configuración Nginx
└── frontend/
    └── Dockerfile              # Angular + Nginx
```

## Comandos Útiles

### Gestión de Contenedores

```bash
# Detener todos los servicios
docker compose down

# Detener y eliminar volúmenes (⚠️ borra datos de la BD)
docker compose down -v

# Reiniciar un servicio específico
docker compose restart backend

# Ver logs en tiempo real
docker compose logs -f backend

# Acceder a un contenedor
docker exec -it aplicacionwebgestionvoluntariado-backend-1 /bin/bash
```

### Troubleshooting

```bash
# Verificar que Docker está corriendo
docker ps

# Ver uso de recursos
docker stats

# Limpiar imágenes no usadas
docker image prune -a

# Reconstruir forzando sin caché
docker compose build --no-cache
```

## Problemas Conocidos

### SQL Server no inicia

**Síntoma:** Contenedor `db` se reinicia constantemente.

**Causa Posible:** Contraseña débil o problemas de permisos en el volumen.

**Solución:**
1. Verificar que la contraseña cumple los requisitos
2. Eliminar volúmenes y recrear:
   ```bash
   docker compose down -v
   docker compose up -d
   ```

### Backend no conecta a la BD

**Verificar:**
1. Que el contenedor `db` está corriendo: `docker compos ps`
2. Los logs de la BD: `docker compose logs db`
3. La variable `DATABASE_URL` en `docker-compose.yml`

## Desarrollo Local vs Docker

Para desarrollo local (Windows con XAMPP):
- Usar `.env.local` con `127.0.0.1` como host
- SQL Server drivers ya instalados en XAMPP

Para Docker:
- Los servicios se comunican por nombre (`db`, `backend`)  
- Drivers SQL Server incluidos en el Dockerfile

## Acceso a la Aplicación

Una vez todos los contenedores estén corriendo:

- **Frontend**: http://localhost
- **Backend API**: http://localhost:8000
- **SQL Server**: `localhost:1433` (para clientes externos como SSMS)

## Próximos Pasos

- [ ] Configurar HTTPS con certificados SSL
- [ ] Añadir health checks a `docker-compose.yml`
- [ ] Implementar CI/CD pipeline
- [ ] Configurar backup automático de la base de datos
