import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import {
  ChangePasswordPayload,
  Profil,
  UpdateProfilPayload,
  User,
} from '../../../shared/models/user.model';

@Injectable({ providedIn: 'root' })
export class ProfilService {
  private readonly api = inject(ApiService);

  get(): Observable<Profil> {
    return this.api
      .get<{ profil: Profil }>('profil')
      .pipe(map((res) => res.profil));
  }

  update(payload: UpdateProfilPayload): Observable<{ user: User; profil: Profil | null }> {
    return this.api.put<{ user: User; profil: Profil | null }>('profil', payload);
  }

  changePassword(payload: ChangePasswordPayload): Observable<void> {
    return this.api.put<void>('profil/password', payload);
  }

  uploadPhoto(file: File): Observable<Profil> {
    const formData = new FormData();
    formData.append('photo', file);

    return this.api
      .post<{ profil: Profil }>('profil/photo', formData)
      .pipe(map((res) => res.profil));
  }
}
