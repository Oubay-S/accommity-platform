import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';
import { Annonce } from '../../../shared/models/annonce.model';

@Injectable({ providedIn: 'root' })
export class AnnonceService {
  private api = inject(ApiService);

  getAnnonces(filtres?: any): Observable<Annonce[]> { return this.api.get<Annonce[]>('annonces', filtres); }
  getAnnonceById(id: number): Observable<Annonce> { return this.api.get<Annonce>(`annonces/${id}`); }
  creerAnnonce(data: any): Observable<Annonce> { return this.api.post<Annonce>('hote/annonces', data); }
}
