# Étape 1 : build de la SPA Angular
FROM node:22-alpine AS build

WORKDIR /app

COPY frontend/package*.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build:prod

# Étape 2 : serveur web de production local
FROM nginx:1.27-alpine

COPY docker/nginx/local-deploy.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist/accommity-frontend/browser /usr/share/nginx/html
COPY backend/public/images /usr/share/nginx/html/images

EXPOSE 80
