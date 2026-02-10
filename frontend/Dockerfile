# Etapa 1: Construcción
FROM node:18 AS build-step

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm install --legacy-peer-deps

COPY . .

# Construir la aplicación para producción
RUN npm run build -- --configuration production

# Etapa 2: Servidor Nginx
FROM nginx:alpine

# Copiar el build de Angular al directorio de Nginx
COPY --from=build-step /app/dist/gestion-voluntariado/browser /usr/share/nginx/html

# Copiar el template de env
COPY --from=build-step /app/src/assets/env.template.js /usr/share/nginx/html/env.template.js

# Script para generar env.js en runtime
RUN echo '#!/bin/sh' > /docker-entrypoint.d/40-generate-env.sh && \
    echo 'echo "Generating env.js with API_URL=${API_URL}"' >> /docker-entrypoint.d/40-generate-env.sh && \
    echo 'envsubst < /usr/share/nginx/html/env.template.js > /usr/share/nginx/html/env.js' >> /docker-entrypoint.d/40-generate-env.sh && \
    chmod +x /docker-entrypoint.d/40-generate-env.sh

# Variables de entorno por defecto
ENV API_URL=http://localhost:8000/api

# Exponer el puerto 80
EXPOSE 80

