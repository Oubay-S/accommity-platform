export const environment = {
  production: true,
  /**
   * En déploiement local, la SPA et l'API sont servies par le même nginx
   * sur http://localhost:8080 : `/api` reste donc une URL relative.
   */
  apiUrl: '/api'
};
