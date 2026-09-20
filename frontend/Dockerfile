# VITE_API_URL se hornea en el bundle en build time (Vite no lee variables
# de entorno en runtime), así que hace falta pasarla como build arg apuntando
# a la URL pública del backend ya desplegado.
FROM node:20-alpine AS build
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

ARG VITE_API_URL
ENV VITE_API_URL=$VITE_API_URL
RUN npm run build

# Server final: nginx sirve los estáticos y resuelve las rutas de React
# Router devolviendo siempre index.html (ver default.conf.template). Usa el
# templating propio de la imagen oficial de nginx (envsubst sobre
# /etc/nginx/templates/*.template -> conf.d), no hace falta entrypoint propio.
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY docker/nginx.default.conf.template /etc/nginx/templates/default.conf.template

ENV PORT=8080
EXPOSE 8080
