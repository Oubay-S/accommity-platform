import { computed, inject, Injectable, signal } from '@angular/core';
import { catchError, finalize, map, Observable, of, tap } from 'rxjs';
import { ApiService } from './api.service';
import { TokenService } from './token.service';
import { AuthData, LoginPayload, RegisterPayload, User } from '../../shared/models/user.model';

/**
 * Source de vérité de la session côté Angular.
 *
 * `bootstrap()` est appelé au démarrage (provideAppInitializer) : il recharge
 * l'utilisateur depuis /auth/me si un token existe, ce qui permet aux guards
 * de fonctionner après un rafraîchissement de page.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly tokens = inject(TokenService);

  private readonly _user = signal<User | null>(null);

  readonly currentUser = this._user.asReadonly();
  readonly isAuthenticated = computed(() => this._user() !== null);
  readonly isAdmin = computed(() => this._user()?.est_admin === true);
  readonly isHote = computed(() => this._user()?.est_hote === true);
  readonly isHoteVerifie = computed(() => this._user()?.est_hote_verifie === true);

  login(payload: LoginPayload): Observable<AuthData> {
    return this.api
      .post<AuthData>('auth/login', payload)
      .pipe(tap((data) => this.setSession(data)));
  }

  register(payload: RegisterPayload): Observable<AuthData> {
    return this.api
      .post<AuthData>('auth/register', payload)
      .pipe(tap((data) => this.setSession(data)));
  }

  /** Recharge la session au démarrage de l'application. */
  bootstrap(): Observable<User | null> {
    if (!this.tokens.hasToken()) {
      return of(null);
    }

    return this.api.get<{ user: User }>('auth/me').pipe(
      tap((res) => this._user.set(res.user)),
      map((res) => res.user),
      catchError(() => {
        this.clearSession();
        return of(null);
      }),
    );
  }

  refreshUser(): Observable<User> {
    return this.api.get<{ user: User }>('auth/me').pipe(
      tap((res) => this._user.set(res.user)),
      map((res) => res.user),
    );
  }

  logout(): Observable<void> {
    return this.api.post<void>('auth/logout').pipe(
      catchError(() => of(undefined)),
      finalize(() => this.clearSession()),
      map(() => undefined),
    );
  }

  clearSession(): void {
    this.tokens.removeToken();
    this._user.set(null);
  }

  setUser(user: User): void {
    this._user.set(user);
  }

  private setSession(data: AuthData): void {
    this.tokens.saveToken(data.token);
    this._user.set(data.user);
  }
}
