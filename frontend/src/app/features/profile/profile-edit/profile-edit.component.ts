import { Component, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import {
  AbstractControl,
  FormBuilder,
  FormGroup,
  ReactiveFormsModule,
  ValidationErrors,
  Validators,
} from '@angular/forms';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { Profil } from '../../../shared/models/user.model';
import { ProfilService } from '../services/profil.service';

function passwordsMatch(group: AbstractControl): ValidationErrors | null {
  const password = group.get('password')?.value as string;
  const confirmation = group.get('password_confirmation')?.value as string;

  return password === confirmation ? null : { passwordsMismatch: true };
}

@Component({
  selector: 'app-profile-edit',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, RouterLinkActive],
  templateUrl: './profile-edit.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './profile-edit.component.scss'
})
export class ProfileEditComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly profils = inject(ProfilService);
  private readonly toast = inject(ToastService);

  readonly user = this.auth.currentUser;
  readonly saving = signal(false);
  readonly uploading = signal(false);
  readonly changingPassword = signal(false);
  readonly count = signal(0);
  readonly showCurrent = signal(false);
  readonly showNew = signal(false);

  readonly identity = this.fb.nonNullable.group({
    nom: ['', [Validators.required, Validators.maxLength(255)]],
    prenom: ['', [Validators.required, Validators.maxLength(255)]],
    email: ['', [Validators.required, Validators.email]],
    telephone: [''],
    description: ['', [Validators.maxLength(1000)]],
  });

  readonly password = this.fb.nonNullable.group(
    {
      current_password: ['', [Validators.required]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: [passwordsMatch] },
  );

  constructor() {
    const user = this.auth.currentUser();

    if (user) {
      this.identity.setValue({
        nom: user.nom,
        prenom: user.prenom,
        email: user.email,
        telephone: user.telephone ?? '',
        description: user.profil?.description ?? '',
      });
      this.count.set(user.profil?.description?.length ?? 0);
    }

    this.identity.controls.description.valueChanges
      .pipe(takeUntilDestroyed())
      .subscribe((value) => this.count.set(value.length));
  }

  get description() {
    return this.identity.controls.description;
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

  saveIdentity(): void {
    if (this.identity.invalid || this.saving()) {
      this.identity.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    const value = this.identity.getRawValue();

    this.profils
      .update({
        nom: value.nom.trim(),
        prenom: value.prenom.trim(),
        email: value.email.trim(),
        telephone: value.telephone.trim() || null,
        description: value.description.trim() || null,
      })
      .subscribe({
        next: (res) => {
          this.auth.setUser(res.user);
          this.saving.set(false);
          this.toast.success('Profil enregistré.');
        },
        error: (err: ApiError) => {
          this.saving.set(false);
          this.toast.error(err.message);
          this.applyErrors(this.identity, err);
        },
      });
  }

  changePassword(): void {
    if (this.password.invalid || this.changingPassword()) {
      this.password.markAllAsTouched();
      return;
    }

    this.changingPassword.set(true);

    this.profils.changePassword(this.password.getRawValue()).subscribe({
      next: () => {
        this.changingPassword.set(false);
        this.password.reset();
        this.toast.success('Mot de passe mis à jour. Les autres sessions ont été déconnectées.');
      },
      error: (err: ApiError) => {
        this.changingPassword.set(false);
        this.toast.error(err.message);
        this.applyErrors(this.password, err);
      },
    });
  }

  private applyProfil(profil: Profil): void {
    const user = this.auth.currentUser();

    if (user) {
      this.auth.setUser({ ...user, profil });
    }
  }

  private applyErrors(form: FormGroup, err: ApiError): void {
    for (const [field, messages] of Object.entries(err.errors ?? {})) {
      form.get(field)?.setErrors({ server: messages[0] });
    }
  }
}
