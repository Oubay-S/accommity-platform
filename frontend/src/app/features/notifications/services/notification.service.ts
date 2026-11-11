import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';
import { Notification } from '../../../shared/models/notification.model';

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private api = inject(ApiService);

  getNotifications(): Observable<Notification[]> { return this.api.get<Notification[]>('notifications'); }
}
