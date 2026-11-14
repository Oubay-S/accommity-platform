import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import { Profil } from '../../../shared/models/user.model';

@Injectable({ providedIn: 'root' })
export class ProfilService {
  private readonly api = inject(ApiService);

  get(): Observable<Profil> {
    return this.api
      .get<{ profil: Profil }>('profil')
      .pipe(map((res) => res.profil));
  }

  update(description: string | null): Observable<Profil> {
    return this.api
      .put<{ profil: Profil }>('profil', { description })
      .pipe(map((res) => res.profil));
  }

  uploadPhoto(file: File): Observable<Profil> {
    const formData = new FormData();
    formData.append('photo', file);

    return this.api
      .post<{ profil: Profil }>('profil/photo', formData)
      .pipe(map((res) => res.profil));
  }
}
