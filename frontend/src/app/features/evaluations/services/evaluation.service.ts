import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import {
  Evaluation,
  EvaluationCreatePayload,
  EvaluationDonneesPage,
  EvaluationPage,
  EvaluationSignalementPayload,
  ReservationAEvaluer,
  TicketLitigeSignalement,
} from '../../../shared/models/evaluation.model';

@Injectable({ providedIn: 'root' })
export class EvaluationService {
  private readonly api = inject(ApiService);

  listerPourAnnonce(
    annonceId: string,
    params: { note?: number; page?: number; per_page?: number } = {},
  ): Observable<EvaluationPage> {
    return this.api.get<EvaluationPage>(`annonces/${annonceId}/avis`, params);
  }

  creer(payload: EvaluationCreatePayload): Observable<{ evaluation: Evaluation }> {
    return this.api.post<{ evaluation: Evaluation }>('evaluations', payload);
  }

  signaler(
    evaluationId: string,
    payload: EvaluationSignalementPayload,
  ): Observable<{ ticket: TicketLitigeSignalement }> {
    return this.api.post<{ ticket: TicketLitigeSignalement }>(
      `evaluations/${evaluationId}/signaler`,
      payload,
    );
  }

  aEvaluer(): Observable<{ items: ReservationAEvaluer[] }> {
    return this.api.get<{ items: ReservationAEvaluer[] }>('evaluations/a-evaluer');
  }

  recues(params: { page?: number; per_page?: number } = {}): Observable<EvaluationPage> {
    return this.api.get<EvaluationPage>('evaluations/recues', params);
  }

  donnees(params: { page?: number; per_page?: number } = {}): Observable<EvaluationDonneesPage> {
    return this.api.get<EvaluationDonneesPage>('evaluations/donnees', params);
  }

  /** Utilisé par les écrans qui n'ont besoin que de la liste. */
  listerDonnees(params: { page?: number; per_page?: number } = {}): Observable<Evaluation[]> {
    return this.donnees(params).pipe(map((page) => page.items));
  }
}
