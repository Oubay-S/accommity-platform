import { Component, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { Notification, TypeAlerte } from '../../../shared/models/notification.model';
import { NotificationService } from '../services/notification.service';

@Component({
  selector: 'app-notification-list',
  standalone: true,
  templateUrl: './notification-list.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './notification-list.component.scss'
})
export class NotificationListComponent {
  private readonly notifications = inject(NotificationService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  readonly items = signal<Notification[]>([]);
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly unread = this.notifications.unread;
  readonly loading = signal(false);
  readonly filter = signal<'toutes' | 'non_lues'>('toutes');

  constructor() {
    this.load(true);
  }

  setFilter(filter: 'toutes' | 'non_lues'): void {
    if (this.filter() === filter) {
      return;
    }

    this.filter.set(filter);
    this.load(true);
  }

  load(reset: boolean): void {
    if (this.loading()) {
      return;
    }

    this.loading.set(true);
    const page = reset ? 1 : this.page() + 1;

    this.notifications
      .getPage({ page, par_page: 15, non_lues: this.filter() === 'non_lues' })
      .subscribe({
        next: (res) => {
          this.items.update((list) => (reset ? res.items : [...list, ...res.items]));
          this.page.set(res.pagination.current_page);
          this.lastPage.set(res.pagination.last_page);
          this.total.set(res.pagination.total);
          this.loading.set(false);
        },
        error: (err: ApiError) => {
          this.loading.set(false);
          this.toast.error(err.message);
        },
      });
  }

  loadMore(): void {
    if (this.page() < this.lastPage()) {
      this.load(false);
    }
  }

  open(notification: Notification): void {
    if (!notification.est_lue) {
      this.notifications.markRead(notification.id_notification).subscribe({
        next: (updated) => {
          this.items.update((list) =>
            list.map((item) =>
              item.id_notification === updated.id_notification ? updated : item,
            ),
          );
        },
      });
    }

    const reservationId = notification.data?.['reservation_id'];

    if (typeof reservationId === 'string') {
      void this.router.navigate([
        this.auth.isHote() ? '/reservations/hote/demandes' : '/reservations/mes-voyages',
      ]);
    }
  }

  markAll(): void {
    this.notifications.markAllRead().subscribe({
      next: () => {
        this.items.update((list) => list.map((item) => ({ ...item, est_lue: true })));
        this.toast.success('Toutes les notifications sont marquées comme lues.');
      },
    });
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

    return new Date(iso).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
  }
}
