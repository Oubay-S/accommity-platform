/**
 * Proxy du dev-server Angular : `/api` → API Laravel (pas de CORS).
 *
 * - En local (`npm start`) : nginx/artisan écoute sur http://127.0.0.1:8000.
 * - Dans Docker : 127.0.0.1 est le conteneur frontend lui-même ; docker-compose
 *   définit API_PROXY_TARGET=http://nginx pour joindre le service nginx.
 */
export default {
  '/api': {
    target: process.env.API_PROXY_TARGET || 'http://127.0.0.1:8000',
    secure: false,
    changeOrigin: false,
    logLevel: 'warn',
  },
};
