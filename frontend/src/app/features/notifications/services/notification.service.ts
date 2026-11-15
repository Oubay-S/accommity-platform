import { inject, Injectable, signal } from '@angular/core';
import { map, Observable, tap } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import { Notification, NotificationPage } from '../../../shared/models/notification.model';

/**
 * Source de vérité du compteur « non lues » : le badge de la cloche et la
 * page Notifications restent synchronisés.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly api = inject(ApiService);

  private readonly _unread = signal(0);
  readonly unread = this._unread.asReadonly();

  getPage(params?: { page?: number; par_page?: number; non_lues?: boolean }): Observable<NotificationPage> {
    return this.api
      .get<NotificationPage>('notifications', params)
      .pipe(tap((page) => this._unread.set(page.non_lues)));
  }

  getUnreadCount(): Observable<number> {
    return this.api
      .get<{ non_lues: number }>('notifications/non-lues')
      .pipe(
        map((res) => res.non_lues),
        tap((count) => this._unread.set(count)),
      );
  }

  markRead(id: string): Observable<Notification> {
    return this.api
      .put<{ notification: Notification }>(`notifications/${id}/lue`)
      .pipe(
        map((res) => res.notification),
        tap(() => this.decrementUnread()),
      );
  }

  markAllRead(): Observable<number> {
    return this.api
      .put<{ marquees: number }>('notifications/tout-lu')
      .pipe(
        map((res) => res.marquees),
        tap(() => this._unread.set(0)),
      );
  }

  setUnread(count: number): void {
    this._unread.set(Math.max(0, count));
  }

  decrementUnread(): void {
    this._unread.update((count) => Math.max(0, count - 1));
  }
}
