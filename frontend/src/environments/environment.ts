export const environment = {
  production: false,
  /**
   * En développement, `/api` est proxifié vers l'API Laravel par le dev-server
   * Angular (voir proxy.conf.mjs) : pas de CORS, pas de port en dur.
   */
  apiUrl: '/api'
};
