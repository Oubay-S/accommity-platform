import { Injectable, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { ApiService } from './api.service';
import { TokenService } from './token.service';
import { User, LoginCredentials, RegisterData } from '../../shared/models/user.model';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private api = inject(ApiService);
  private tokenService = inject(TokenService);

  currentUser = signal<User | null>(null);

  login(credentials: LoginCredentials): Observable<{ token: string; user: User }> {
    return this.api.post<{ token: string; user: User }>('auth/login', credentials).pipe(
      tap(res => {
        this.tokenService.saveToken(res.token);
        this.currentUser.set(res.user);
      })
    );
  }

  register(data: RegisterData): Observable<{ user: User }> {
    return this.api.post<{ user: User }>('auth/register', data);
  }

  logout(): void {
    this.tokenService.removeToken();
    this.currentUser.set(null);
  }

  isAuthenticated(): boolean {
    return !!this.tokenService.getToken();
  }

  hasRole(role: string): boolean {
    return this.currentUser()?.role === role;
  }

  isHoteVerifie(): boolean {
    return this.currentUser()?.is_hote_verifie ?? false;
  }

  isAdmin(): boolean {
    return this.currentUser()?.role === 'admin';
  }
}
