import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';
import { Reservation, ReservationCreateDto } from '../../../shared/models/reservation.model';

@Injectable({ providedIn: 'root' })
export class ReservationService {
  private api = inject(ApiService);

  creerReservation(dto: ReservationCreateDto): Observable<Reservation> { return this.api.post<Reservation>('reservations', dto); }
  getMesVoyages(): Observable<Reservation[]> { return this.api.get<Reservation[]>('reservations/mes-voyages'); }
  annulerReservation(id: number, motif: string): Observable<any> { return this.api.post(`reservations/${id}/annuler`, { motif }); }
}
