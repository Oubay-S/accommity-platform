import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink, RouterLinkActive } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { Profil } from '../../../shared/models/user.model';
import { ProfilService } from '../services/profil.service';

@Component({
  selector: 'app-profile-edit',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, RouterLinkActive],
  templateUrl: './profile-edit.component.html',
  styleUrl: './profile-edit.component.scss'
})
export class ProfileEditComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly profils = inject(ProfilService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  readonly user = this.auth.currentUser;
  readonly saving = signal(false);
  readonly uploading = signal(false);
  readonly count = signal(0);

  readonly form = this.fb.nonNullable.group({
    description: ['', [Validators.maxLength(1000)]],
  });

  constructor() {
    const description = this.auth.currentUser()?.profil?.description ?? '';
    this.form.setValue({ description });
    this.count.set(description.length);

    this.form.controls.description.valueChanges
      .pipe(takeUntilDestroyed())
      .subscribe((value) => this.count.set(value.length));
  }

  initials(): string {
    const user = this.user();
    return user ? `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase() : '?';
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
      return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      this.toast.error('Format accepté : JPG, PNG ou WEBP.');
      input.value = '';
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      this.toast.error('La photo doit faire moins de 2 Mo.');
      input.value = '';
      return;
    }

    this.uploading.set(true);

    this.profils.uploadPhoto(file).subscribe({
      next: (profil) => {
        this.applyProfil(profil);
        this.uploading.set(false);
        this.toast.success('Photo de profil mise à jour.');
        input.value = '';
      },
      error: (err: ApiError) => {
        this.uploading.set(false);
        this.toast.error(err.message);
        input.value = '';
      },
    });
  }

  save(): void {
    if (this.form.invalid || this.saving()) {
      this.form.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    const description = this.form.controls.description.value.trim();

    this.profils.update(description || null).subscribe({
      next: (profil) => {
        this.applyProfil(profil);
        this.saving.set(false);
        this.toast.success('Profil enregistré.');
        void this.router.navigate(['/profil']);
      },
      error: (err: ApiError) => {
        this.saving.set(false);
        this.toast.error(err.message);
      },
    });
  }

  private applyProfil(profil: Profil): void {
    const user = this.auth.currentUser();

    if (user) {
      this.auth.setUser({ ...user, profil });
    }
  }
}
