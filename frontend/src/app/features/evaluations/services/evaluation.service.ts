import { Injectable, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class EvaluationService {
  private api = inject(ApiService);

  soumettreAvis(avis: any): Observable<any> { return this.api.post('evaluations', avis); }
}
