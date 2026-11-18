import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import {
  AnnonceDetail,
  AnnonceHote,
  AnnonceHotePayload,
  AnnonceReferences,
  AnnonceResume,
  CalendrierHote,
  CriteresRecherche,
  MesAnnoncesResultat,
} from '../../../shared/models/annonce.model';
import { PaginatedData } from '../../../shared/models/api-response.model';

@Injectable({ providedIn: 'root' })
export class AnnonceService {
  private readonly api = inject(ApiService);

  /* ----------------------------------------------------------------------- */
  /* Catalogue public (Reda)                                                 */
  /* ----------------------------------------------------------------------- */

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

  /* ----------------------------------------------------------------------- */
  /* Espace hôte (RG06, RG07, RG08, RG09)                                    */
  /* ----------------------------------------------------------------------- */

  getReferences(): Observable<AnnonceReferences> {
    return this.api.get<AnnonceReferences>('annonces/references');
  }

  getMesAnnonces(params: {
    statut?: string;
    recherche?: string;
    page?: number;
    per_page?: number;
  } = {}): Observable<MesAnnoncesResultat> {
    return this.api.get<MesAnnoncesResultat>('hote/annonces', params);
  }

  getAnnonceHote(id: string): Observable<AnnonceHote> {
    return this.api.get<AnnonceHote>(`hote/annonces/${id}`);
  }

  creerAnnonce(data: AnnonceHotePayload | FormData): Observable<AnnonceHote> {
    return this.api.post<AnnonceHote>('hote/annonces', data);
  }

  modifierAnnonce(id: string, data: Partial<AnnonceHotePayload>): Observable<AnnonceHote> {
    return this.api.put<AnnonceHote>(`hote/annonces/${id}`, data);
  }

  supprimerAnnonce(id: string): Observable<void> {
    return this.api.delete<void>(`hote/annonces/${id}`);
  }

  ajouterPhotos(id: string, formData: FormData): Observable<AnnonceHote> {
    return this.api.post<AnnonceHote>(`hote/annonces/${id}/photos`, formData);
  }

  supprimerPhoto(id: string, url: string): Observable<AnnonceHote> {
    return this.api.delete<AnnonceHote>(`hote/annonces/${id}/photos?url=${encodeURIComponent(url)}`);
  }

  getCalendrierHote(id: string, debut?: string, fin?: string): Observable<CalendrierHote> {
    return this.api.get<CalendrierHote>(`hote/annonces/${id}/calendrier`, { debut, fin });
  }

  bloquerDates(
    id: string,
    dateDebut: string,
    dateFin: string,
  ): Observable<{ id_calendrier: string; date_debut: string; date_fin: string }> {
    return this.api.post(`hote/annonces/${id}/bloquer`, {
      date_debut: dateDebut,
      date_fin: dateFin,
    });
  }

  debloquerDates(id: string, dateDebut: string, dateFin: string): Observable<{ blocages_supprimes: number }> {
    return this.api.post(`hote/annonces/${id}/debloquer`, {
      date_debut: dateDebut,
      date_fin: dateFin,
    });
  }
}
