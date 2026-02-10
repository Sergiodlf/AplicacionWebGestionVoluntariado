FROM nginx:alpine

# Borramos la configuración por defecto para que no haya conflictos
RUN rm /etc/nginx/conf.d/default.conf

# Copiamos tu archivo (asegúrate de que el archivo local se llame default.conf)
COPY default.conf /etc/nginx/conf.d/default.conf