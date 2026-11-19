import { Component, OnInit, inject, signal, viewChild, ChangeDetectionStrategy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { AnnonceHote, AnnonceReferences } from '../../../shared/models/annonce.model';
import { AnnonceService } from '../services/annonce.service';
import { AnnonceFormComponent, AnnonceFormSubmit } from '../annonce-form/annonce-form.component';

@Component({
  selector: 'app-annonce-edit',
  standalone: true,
  imports: [AnnonceFormComponent],
  templateUrl: './annonce-edit.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './annonce-edit.component.scss',
})
export class AnnonceEditComponent implements OnInit {
  private readonly annonces = inject(AnnonceService);
  private readonly toast = inject(ToastService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  private readonly form = viewChild(AnnonceFormComponent);

  readonly references = signal<AnnonceReferences | null>(null);
  readonly annonce = signal<AnnonceHote | null>(null);
  readonly submitting = signal(false);
  readonly errors = signal<Record<string, string[]> | null>(null);
  readonly loading = signal(true);

  private annonceId = '';

  ngOnInit(): void {
    this.annonceId = this.route.snapshot.paramMap.get('id') ?? '';

    if (!this.annonceId) {
      this.router.navigate(['/hote/annonces']);
      return;
    }

    this.annonces.getReferences().subscribe({
      next: (references) => this.references.set(references),
      error: () => this.toast.error('Impossible de charger les référentiels du formulaire.'),
    });

    this.annonces.getAnnonceHote(this.annonceId).subscribe({
      next: (annonce) => {
        this.annonce.set(annonce);
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.loading.set(false);
        this.toast.error(
          error instanceof ApiError ? error.message : 'Annonce introuvable ou accès refusé.',
        );
        this.router.navigate(['/hote/annonces']);
      },
    });
  }

  enregistrer({ payload, photos }: AnnonceFormSubmit): void {
    this.submitting.set(true);
    this.errors.set(null);

    this.annonces.modifierAnnonce(this.annonceId, payload).subscribe({
      next: (annonce) => {
        this.annonce.set(annonce);

        if (photos.length > 0) {
          const body = new FormData();
          for (const photo of photos) {
            body.append('photos[]', photo);
          }

          this.annonces.ajouterPhotos(this.annonceId, body).subscribe({
            next: (miseAJour) => {
              this.annonce.set(miseAJour);
              this.form()?.setExistingPhotos(miseAJour.photos);
              this.finir();
            },
            error: (error: unknown) => {
              this.submitting.set(false);
              this.toast.error(
                error instanceof ApiError ? error.message : 'L’ajout des photos a échoué.',
              );
            },
          });
          return;
        }

        this.finir();
      },
      error: (error: unknown) => {
        this.submitting.set(false);
        this.errors.set(error instanceof ApiError ? error.errors : null);
        this.toast.error(
          error instanceof ApiError ? error.message : 'La mise à jour de l’annonce a échoué.',
        );
      },
    });
  }

  supprimerPhoto(url: string): void {
    this.annonces.supprimerPhoto(this.annonceId, url).subscribe({
      next: (annonce) => {
        this.annonce.set(annonce);
        this.form()?.setExistingPhotos(annonce.photos);
        this.toast.success('Photo supprimée.');
      },
      error: (error: unknown) => {
        this.toast.error(
          error instanceof ApiError ? error.message : 'La suppression de la photo a échoué.',
        );
      },
    });
  }

  private finir(): void {
    this.submitting.set(false);
    this.toast.success('Annonce mise à jour.');
    this.router.navigate(['/hote/annonces']);
  }
}
