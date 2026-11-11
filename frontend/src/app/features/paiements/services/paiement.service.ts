import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class PaiementService {
  private api = inject(ApiService);

  payer(reservationId: number, donneesCarte: any): Observable<any> { return this.api.post(`paiements/${reservationId}`, donneesCarte); }
}
