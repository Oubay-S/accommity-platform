import { Component, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import {
  NavigationEnd,
  Router,
  RouterLink,
  RouterLinkActive,
  RouterOutlet,
} from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { filter } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './admin-layout.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './admin-layout.component.scss'
})
export class AdminLayoutComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  readonly user = this.auth.currentUser;
  readonly section = signal('Tableau de bord');
  readonly sidebarOpen = signal(true);

  toggleSidebar(): void {
    this.sidebarOpen.update((v) => !v);
  }

  private readonly titles: Record<string, string> = {
    '/admin': 'Tableau de bord',
    '/admin/utilisateurs': 'Utilisateurs',
    '/admin/verifications': "Vérifications d'identité",
    '/admin/annonces': 'Annonces',
    '/admin/reservations': 'Réservations',
    '/admin/avis': 'Avis signalés',
    '/admin/litiges': 'Litiges',
    '/admin/transactions': 'Transactions',
  };

  constructor() {
    this.updateSection();

    this.router.events
      .pipe(
        filter((event): event is NavigationEnd => event instanceof NavigationEnd),
        takeUntilDestroyed(),
      )
      .subscribe(() => this.updateSection());
  }

  fullName(): string {
    const user = this.user();
    return user ? `${user.prenom} ${user.nom}` : 'Administrateur';
  }

  initials(): string {
    const user = this.user();
    return user ? `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase() : 'AD';
  }

  logout(): void {
    this.auth.logout().subscribe(() => {
      void this.router.navigate(['/auth/login']);
    });
  }

  private updateSection(): void {
    const path = this.router.url.split('?')[0];
    this.section.set(this.titles[path] ?? 'Administration');
  }
}
