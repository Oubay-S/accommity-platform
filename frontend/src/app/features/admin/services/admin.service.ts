import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class AdminService {
  private api = inject(ApiService);

  getStats(): Observable<any> { return this.api.get('admin/stats'); }
}
