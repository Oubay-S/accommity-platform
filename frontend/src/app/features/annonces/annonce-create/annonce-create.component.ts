import { Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { AnnonceReferences } from '../../../shared/models/annonce.model';
import { AnnonceService } from '../services/annonce.service';
import { AnnonceFormComponent, AnnonceFormSubmit } from '../annonce-form/annonce-form.component';

@Component({
  selector: 'app-annonce-create',
  standalone: true,
  imports: [AnnonceFormComponent],
  templateUrl: './annonce-create.component.html',
  styleUrl: './annonce-create.component.scss',
})
export class AnnonceCreateComponent {
  private readonly annonces = inject(AnnonceService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  readonly references = signal<AnnonceReferences | null>(null);
  readonly submitting = signal(false);
  readonly errors = signal<Record<string, string[]> | null>(null);

  constructor() {
    this.annonces.getReferences().subscribe({
      next: (references) => this.references.set(references),
      error: () => this.toast.error('Impossible de charger les référentiels du formulaire.'),
    });
  }

  enregistrer({ payload, photos }: AnnonceFormSubmit): void {
    if (photos.length === 0) {
      this.toast.error('Ajoutez au moins une photo pour créer l’annonce.');
      return;
    }

    this.submitting.set(true);
    this.errors.set(null);

    const body = new FormData();

    for (const [cle, valeur] of Object.entries(payload)) {
      if (valeur === null || valeur === undefined) {
        continue;
      }

      if (Array.isArray(valeur)) {
        for (const item of valeur) {
          body.append('equipements[]', item);
        }
        continue;
      }

      body.append(cle, String(valeur));
    }

    for (const photo of photos) {
      body.append('photos[]', photo);
    }

    this.annonces.creerAnnonce(body).subscribe({
      next: () => {
        this.submitting.set(false);
        this.toast.success('Annonce créée. Elle sera publiée après validation.');
        this.router.navigate(['/hote/annonces']);
      },
      error: (error: unknown) => {
        this.submitting.set(false);
        this.errors.set(error instanceof ApiError ? error.errors : null);
        this.toast.error(
          error instanceof ApiError ? error.message : 'La création de l’annonce a échoué.',
        );
      },
    });
  }
}
