import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiError, ApiResponse } from '../../shared/models/api-response.model';

/**
 * Client HTTP unique.
 *
 * Déballe l'enveloppe `ApiResponse<T>` : les services reçoivent directement
 * `data`. Toute erreur est normalisée en `ApiError` par l'ErrorInterceptor.
 */
@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiUrl;

  get<T>(endpoint: string, params?: Record<string, unknown>): Observable<T> {
    return this.http
      .get<ApiResponse<T>>(this.url(endpoint), { params: this.toParams(params) })
      .pipe(map((res) => this.unwrap(res)));
  }

  post<T>(endpoint: string, body: unknown = {}): Observable<T> {
    return this.http
      .post<ApiResponse<T>>(this.url(endpoint), body)
      .pipe(map((res) => this.unwrap(res)));
  }

  put<T>(endpoint: string, body: unknown = {}): Observable<T> {
    return this.http
      .put<ApiResponse<T>>(this.url(endpoint), body)
      .pipe(map((res) => this.unwrap(res)));
  }

  delete<T>(endpoint: string): Observable<T> {
    return this.http
      .delete<ApiResponse<T>>(this.url(endpoint))
      .pipe(map((res) => this.unwrap(res)));
  }

  /** Téléchargement binaire (documents privés) : pas d'enveloppe JSON. */
  getBlob(endpoint: string): Observable<Blob> {
    return this.http.get(this.url(endpoint), { responseType: 'blob' });
  }

  private url(endpoint: string): string {
    return `${this.baseUrl}/${endpoint.replace(/^\//, '')}`;
  }

  private unwrap<T>(res: ApiResponse<T>): T {
    if (!res.success) {
      throw new ApiError(400, res.message, res.errors ?? null, res.error_code ?? null);
    }

    return res.data as T;
  }

  private toParams(params?: Record<string, unknown>): HttpParams | undefined {
    if (!params) {
      return undefined;
    }

    let httpParams = new HttpParams();

    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && value !== '') {
        httpParams = httpParams.set(key, String(value));
      }
    }

    return httpParams;
  }
}
