# Etapa 1: Construcción
FROM node:18 AS build-step

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm install

COPY . .

# Construir la aplicación para producción
RUN npm run build -- --configuration production

# Etapa 2: Servidor Nginx
FROM nginx:alpine

# Copiar el build de Angular al directorio de Nginx
COPY --from=build-step /app/dist/frontend/browser /usr/share/nginx/html

# Exponer el puerto 80
EXPOSE 80
