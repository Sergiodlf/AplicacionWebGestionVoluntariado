FROM mcr.microsoft.com/mssql/server:2022-latest

USER root

# Crear directorio para scripts de inicialización si fuera necesario
RUN mkdir -p /usr/src/app

WORKDIR /usr/src/app

# Cambiar de nuevo a usuario no root para seguridad (mssql user)
USER mssql
