import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import {
  AdminVerification,
  AdminVerificationPage,
} from '../models/admin-verification.model';

@Injectable({ providedIn: 'root' })
export class AdminService {
  private readonly api = inject(ApiService);

  getStats(): Observable<unknown> {
    return this.api.get('admin/stats');
  }

  getVerifications(params: {
    statut?: string;
    recherche?: string;
    page?: number;
    par_page?: number;
  }): Observable<AdminVerificationPage> {
    return this.api.get<AdminVerificationPage>('admin/verifications', params);
  }

  valider(id: string): Observable<AdminVerification> {
    return this.api
      .put<{ verification: AdminVerification }>(`admin/verifications/${id}/valider`)
      .pipe(map((res) => res.verification));
  }

  rejeter(id: string, motif: string): Observable<AdminVerification> {
    return this.api
      .put<{ verification: AdminVerification }>(`admin/verifications/${id}/rejeter`, { motif })
      .pipe(map((res) => res.verification));
  }

  downloadDocument(id: string): Observable<Blob> {
    return this.api.getBlob(`admin/verifications/${id}/document`);
  }
}
