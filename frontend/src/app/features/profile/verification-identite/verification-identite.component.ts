import { Component, computed, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { TypePiece, VerificationIdentite } from '../../../shared/models/user.model';
import { VerificationService } from '../services/verification.service';

@Component({
  selector: 'app-verification-identite',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './verification-identite.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './verification-identite.component.scss'
})
export class VerificationIdentiteComponent {
  private readonly auth = inject(AuthService);
  private readonly verifications = inject(VerificationService);
  private readonly toast = inject(ToastService);

  readonly isHote = this.auth.isHote;

  readonly status = signal<VerificationIdentite | null>(null);
  readonly loading = signal(true);
  readonly submitting = signal(false);
  readonly typePiece = signal<TypePiece>('cin');
  readonly file = signal<File | null>(null);
  readonly error = signal<string | null>(null);

  readonly canSubmit = computed(() => {
    if (!this.isHote()) {
      return false;
    }

    const statut = this.status()?.statut;
    return statut === undefined || statut === 'rejetee';
  });

  constructor() {
    this.load();
  }

  load(): void {
    this.loading.set(true);

    this.verifications.getStatus().subscribe({
      next: (verification) => {
        this.status.set(verification);
        this.loading.set(false);
      },
      error: (err: ApiError) => {
        this.loading.set(false);
        this.toast.error(err.message);
      },
    });
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
      return;
    }

    if (!['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)) {
      this.toast.error('Formats acceptés : JPG, PNG ou PDF.');
      input.value = '';
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      this.toast.error('Le document doit faire moins de 5 Mo.');
      input.value = '';
      return;
    }

    this.file.set(file);
    this.error.set(null);
  }

  removeFile(): void {
    this.file.set(null);
  }

  submit(): void {
    const file = this.file();

    if (!file) {
      this.error.set('Veuillez sélectionner un document.');
      return;
    }

    this.submitting.set(true);
    this.error.set(null);

    this.verifications.submit(this.typePiece(), file).subscribe({
      next: (verification) => {
        this.status.set(verification);
        this.file.set(null);
        this.submitting.set(false);
        this.toast.success('Dossier soumis. Réponse sous 12 à 24 h ouvrées.');
      },
      error: (err: ApiError) => {
        this.submitting.set(false);
        this.error.set(err.message);

        if (err.errorCode === 'VERIFICATION_EN_COURS' || err.errorCode === 'DEJA_VERIFIE') {
          this.load();
        }
      },
    });
  }

  fileName(): string {
    return this.file()?.name ?? '';
  }

  fileSize(): string {
    const file = this.file();
    return file ? `${(file.size / 1024 / 1024).toFixed(2)} Mo` : '';
  }

  statusLabel(): string {
    switch (this.status()?.statut) {
      case 'validee':
        return 'Identité vérifiée';
      case 'en_cours':
        return 'Dossier en cours de revue';
      case 'rejetee':
        return 'Dossier rejeté — action requise';
      default:
        return 'Aucun dossier soumis';
    }
  }

  statusClass(): string {
    switch (this.status()?.statut) {
      case 'validee':
        return 'alert alert--success';
      case 'en_cours':
        return 'alert alert--info';
      case 'rejetee':
        return 'alert alert--danger';
      default:
        return 'alert alert--warning';
    }
  }

  formatDate(iso: string | null): string {
    return iso
      ? new Date(iso).toLocaleString('fr-FR', {
          day: '2-digit',
          month: 'long',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        })
      : '—';
  }
}
