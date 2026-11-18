import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import {
  Annonce,
  AnnonceDetail,
  AnnonceResume,
  CriteresRecherche,
} from '../../../shared/models/annonce.model';
import { PaginatedData } from '../../../shared/models/api-response.model';

@Injectable({ providedIn: 'root' })
export class AnnonceService {
  private readonly api = inject(ApiService);

  rechercher(criteres: CriteresRecherche): Observable<PaginatedData<AnnonceResume>> {
    const params: Record<string, unknown> = {
      ...criteres,
      equipements: criteres.equipements?.length ? criteres.equipements.join(',') : undefined,
    };

    return this.api.get<PaginatedData<AnnonceResume>>('annonces', params);
  }

  getEnVedette(): Observable<AnnonceResume[]> {
    return this.api.get<AnnonceResume[]>('annonces/en-vedette');
  }

  getAnnonce(id: string): Observable<AnnonceDetail> {
    return this.api.get<AnnonceDetail>(`annonces/${id}`);
  }

  getDisponibilites(id: string): Observable<{ dates_indisponibles: string[] }> {
    return this.api.get<{ dates_indisponibles: string[] }>(`annonces/${id}/disponibilites`);
  }

  creerAnnonce(data: unknown): Observable<Annonce> {
    return this.api.post<Annonce>('hote/annonces', data);
  }
}
