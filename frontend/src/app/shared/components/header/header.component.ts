import { Component, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';
import { takeUntilDestroyed, toObservable } from '@angular/core/rxjs-interop';
import { catchError, of, switchMap } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../features/notifications/services/notification.service';
import { Notification, TypeAlerte } from '../../../shared/models/notification.model';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './header.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './header.component.scss'
})
export class HeaderComponent {
  private readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);

  readonly user = this.auth.currentUser;
  readonly isAuthenticated = this.auth.isAuthenticated;
  readonly isAdmin = this.auth.isAdmin;
  readonly isHote = this.auth.isHote;

  readonly bellOpen = signal(false);
  readonly unread = this.notifications.unread;
  readonly latest = signal<Notification[]>([]);
  readonly loading = signal(false);

  constructor() {
    toObservable(this.isAuthenticated)
      .pipe(
        switchMap((authenticated) => {
          if (!authenticated) {
            this.latest.set([]);
            this.notifications.setUnread(0);
            return of(0);
          }

          return this.notifications.getUnreadCount().pipe(catchError(() => of(0)));
        }),
        takeUntilDestroyed(),
      )
      .subscribe();
  }

  toggleBell(): void {
    const open = !this.bellOpen();
    this.bellOpen.set(open);

    if (open) {
      this.loadLatest();
    }
  }

  closeBell(): void {
    this.bellOpen.set(false);
  }

  openNotification(notification: Notification): void {
    if (!notification.est_lue) {
      this.notifications.markRead(notification.id_notification).subscribe({
        next: () => {
          this.latest.update((items) =>
            items.map((item) =>
              item.id_notification === notification.id_notification
                ? { ...item, est_lue: true }
                : item,
            ),
          );
        },
      });
    }

    this.bellOpen.set(false);

    const reservationId = notification.data?.['reservation_id'];
    const target = typeof reservationId === 'string'
      ? (this.isHote() ? '/reservations/hote/demandes' : '/reservations/mes-voyages')
      : '/notifications';

    void this.router.navigate([target]);
  }

  markAllAsRead(): void {
    this.notifications.markAllRead().subscribe({
      next: () => {
        this.latest.update((items) => items.map((item) => ({ ...item, est_lue: true })));
      },
    });
  }

  logout(): void {
    this.auth.logout().subscribe(() => {
      void this.router.navigate(['/']);
    });
  }

  fullName(): string {
    const user = this.user();
    return user ? `${user.prenom} ${user.nom}` : '';
  }

  initials(): string {
    const user = this.user();
    return user
      ? `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase()
      : '?';
  }

  iconFor(type: TypeAlerte): string {
    switch (type) {
      case 'reservation':
        return 'calendar_month';
      case 'paiement':
        return 'payments';
      case 'avis':
        return 'star';
      case 'rappel':
        return 'notifications_active';
      default:
        return 'verified_user';
    }
  }

  timeAgo(iso: string | null): string {
    if (!iso) {
      return '';
    }

    const diff = Date.now() - new Date(iso).getTime();
    const minutes = Math.floor(diff / 60_000);

    if (minutes < 1) {
      return "à l'instant";
    }
    if (minutes < 60) {
      return `il y a ${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
      return `il y a ${hours} h`;
    }

    const days = Math.floor(hours / 24);
    if (days < 7) {
      return `il y a ${days} j`;
    }

    return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
  }

  private loadLatest(): void {
    this.loading.set(true);
    this.notifications.getPage({ par_page: 5 }).subscribe({
      next: (page) => {
        this.latest.set(page.items);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }
}
