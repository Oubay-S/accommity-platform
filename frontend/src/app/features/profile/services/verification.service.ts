import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import { TypePiece, VerificationIdentite } from '../../../shared/models/user.model';

@Injectable({ providedIn: 'root' })
export class VerificationService {
  private readonly api = inject(ApiService);

  getStatus(): Observable<VerificationIdentite | null> {
    return this.api
      .get<{ verification: VerificationIdentite | null }>('profil/verification/statut')
      .pipe(map((res) => res.verification));
  }

  submit(typePiece: TypePiece, document: File): Observable<VerificationIdentite> {
    const formData = new FormData();
    formData.append('type_piece', typePiece);
    formData.append('document', document);

    return this.api
      .post<{ verification: VerificationIdentite }>('profil/verification', formData)
      .pipe(map((res) => res.verification));
  }
}
