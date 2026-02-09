FROM mcr.microsoft.com/mssql/server:2022-latest

# Ejecutar como root para configurar permisos
USER root

# Crear directorio de datos y asignar permisos correctos
RUN mkdir -p /var/opt/mssql/data \
    && chown -R mssql:mssql /var/opt/mssql \
    && chmod -R 755 /var/opt/mssql

# Cambiar de vuelta al usuario mssql
USER mssql

WORKDIR /var/opt/mssql
