import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';
import {
  Paiement,
  DonneesCarte,
  ResultatPaiement,
  RecuDetail
} from '../../../shared/models/paiement.model';
import { PaginatedData } from '../../../shared/models/api-response.model';

@Injectable({ providedIn: 'root' })
export class PaiementService {
  private api = inject(ApiService);

  payer(reservationId: string, carte: DonneesCarte): Observable<ResultatPaiement> {
    return this.api.post<ResultatPaiement>(`paiements/${reservationId}`, carte);
  }

  getRecu(paiementId: string): Observable<RecuDetail> {
    return this.api.get<RecuDetail>(`paiements/recu/${paiementId}`);
  }

  getHistorique(page = 1): Observable<PaginatedData<Paiement>> {
    return this.api.get<PaginatedData<Paiement>>(`paiements/historique?page=${page}`);
  }
}
