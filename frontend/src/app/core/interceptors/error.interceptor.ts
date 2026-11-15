import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { ApiError, ApiResponse } from '../../shared/models/api-response.model';
import { AuthService } from '../services/auth.service';
import { ToastService } from '../services/toast.service';

/**
 * Normalise toutes les erreurs HTTP en `ApiError` et applique la politique
 * globale : 401 → nettoyage de session + redirection, 403/404/429 → toast.
 * Les 422 restent au formulaire (affichage champ par champ).
 */
export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const auth = inject(AuthService);
  const toast = inject(ToastService);

  return next(req).pipe(
    catchError((error: unknown) => {
      if (!(error instanceof HttpErrorResponse)) {
        return throwError(() => error);
      }

      const body = (typeof error.error === 'object' && error.error !== null
        ? (error.error as Partial<ApiResponse<unknown>>)
        : null);

      const apiError = new ApiError(
        error.status,
        body?.message ?? messageForStatus(error.status),
        body?.errors ?? null,
        body?.error_code ?? null,
      );

      const isAuthRequest = req.url.includes('/api/auth/');

      if (error.status === 401 && !isAuthRequest) {
        auth.clearSession();
        const returnUrl = router.url;
        void router.navigate(['/auth/login'], {
          queryParams: returnUrl.startsWith('/auth') ? {} : { returnUrl },
        });
      } else if (!isAuthRequest && [403, 404, 429].includes(error.status)) {
        toast.error(apiError.message);
      } else if (error.status === 0) {
        toast.error('Serveur injoignable. Vérifiez que l’API est démarrée.');
      }

      return throwError(() => apiError);
    }),
  );
};

function messageForStatus(status: number): string {
  switch (status) {
    case 400:
      return 'La requête est invalide.';
    case 401:
      return 'Authentification requise.';
    case 403:
      return 'Accès refusé.';
    case 404:
      return 'Ressource introuvable.';
    case 422:
      return 'Les données fournies sont invalides.';
    case 429:
      return 'Trop de tentatives. Réessayez plus tard.';
    case 500:
      return 'Une erreur interne est survenue.';
    default:
      return 'Une erreur est survenue.';
  }
}
