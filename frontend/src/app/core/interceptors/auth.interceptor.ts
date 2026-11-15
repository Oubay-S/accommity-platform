import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { TokenService } from '../services/token.service';

/** Ajoute le Bearer Token à toutes les requêtes API protégées. */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const tokens = inject(TokenService);
  const isAuthEndpoint = /\/auth\/(login|register)$/.test(req.url);

  if (isAuthEndpoint) {
    return next(req);
  }

  const token = tokens.getToken();
  const headers: Record<string, string> = { Accept: 'application/json' };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  return next(req.clone({ setHeaders: headers }));
};
