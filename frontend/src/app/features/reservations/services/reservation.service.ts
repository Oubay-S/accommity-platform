import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';
import {
  Reservation,
  ReservationCreateDto,
  RepondreReservationDto,
  AnnulerReservationDto
} from '../../../shared/models/reservation.model';
import { ApercuRemboursement } from '../../../shared/models/paiement.model';
import { PaginatedData } from '../../../shared/models/api-response.model';

@Injectable({ providedIn: 'root' })
export class ReservationService {
  private api = inject(ApiService);

  creerReservation(dto: ReservationCreateDto): Observable<Reservation> {
    return this.api.post<Reservation>('reservations', dto);
  }

  getMesVoyages(page = 1): Observable<PaginatedData<Reservation>> {
    return this.api.get<PaginatedData<Reservation>>(`reservations/mes-voyages?page=${page}`);
  }

  getDemandesHote(page = 1): Observable<PaginatedData<Reservation>> {
    return this.api.get<PaginatedData<Reservation>>(`hote/demandes?page=${page}`);
  }

  getReservation(id: string): Observable<Reservation> {
    return this.api.get<Reservation>(`reservations/${id}`);
  }

  repondreReservation(id: string, dto: RepondreReservationDto): Observable<Reservation> {
    return this.api.post<Reservation>(`hote/reservations/${id}/repondre`, dto);
  }

  apercuRemboursement(id: string): Observable<ApercuRemboursement> {
    return this.api.get<ApercuRemboursement>(`reservations/${id}/apercu-remboursement`);
  }

  annulerReservation(id: string, dto: AnnulerReservationDto): Observable<ApercuRemboursement> {
    return this.api.post<ApercuRemboursement>(`reservations/${id}/annuler`, dto);
  }
}
