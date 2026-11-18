import { Component, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import {
  AbstractControl,
  FormBuilder,
  ReactiveFormsModule,
  ValidationErrors,
  Validators,
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';

function passwordsMatch(group: AbstractControl): ValidationErrors | null {
  const password = group.get('password')?.value as string;
  const confirmation = group.get('password_confirmation')?.value as string;

  return password === confirmation ? null : { passwordsMismatch: true };
}

function passwordScore(value: string): number {
  if (!value) {
    return 0;
  }

  let score = Math.min(60, value.length * 7);

  if (/[A-Z]/.test(value)) {
    score += 13;
  }
  if (/[0-9]/.test(value)) {
    score += 14;
  }
  if (/[^A-Za-z0-9]/.test(value)) {
    score += 13;
  }

  return Math.min(100, score);
}

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './register.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './register.component.scss'
})
export class RegisterComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly submitted = signal(false);
  readonly error = signal<string | null>(null);
  readonly showPassword = signal(false);
  readonly showConfirmation = signal(false);
  readonly strength = signal(0);

  readonly form = this.fb.nonNullable.group(
    {
      role: this.fb.nonNullable.control<'voyageur' | 'hote'>('voyageur', Validators.required),
      prenom: ['', [Validators.required, Validators.maxLength(255)]],
      nom: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email]],
      telephone: [''],
      password: ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', [Validators.required]],
      cgu: [false, Validators.requiredTrue],
    },
    { validators: [passwordsMatch] },
  );

  constructor() {
    this.form.controls.password.valueChanges
      .pipe(takeUntilDestroyed())
      .subscribe((value) => this.strength.set(passwordScore(value)));

    this.form.controls.role.valueChanges
      .pipe(takeUntilDestroyed())
      .subscribe((role) => {
        const telephone = this.form.controls.telephone;

        telephone.setValidators(
          role === 'hote'
            ? [Validators.required, Validators.pattern(/^[0-9+().\s-]{9,20}$/)]
            : [],
        );
        telephone.updateValueAndValidity({ emitEvent: false });
      });
  }

  get role() {
    return this.form.controls.role;
  }
  get prenom() {
    return this.form.controls.prenom;
  }
  get nom() {
    return this.form.controls.nom;
  }
  get email() {
    return this.form.controls.email;
  }
  get telephone() {
    return this.form.controls.telephone;
  }
  get password() {
    return this.form.controls.password;
  }
  get confirmation() {
    return this.form.controls.password_confirmation;
  }
  get cgu() {
    return this.form.controls.cgu;
  }

  strengthLabel(): string {
    const value = this.strength();

    if (value === 0) {
      return 'Force du mot de passe';
    }
    if (value < 45) {
      return 'Faible';
    }
    if (value < 70) {
      return 'Correcte';
    }

    return 'Robuste';
  }

  submit(): void {
    this.submitted.set(true);

    if (this.form.invalid || this.loading()) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    const value = this.form.getRawValue();

    this.auth
      .register({
        nom: value.nom.trim(),
        prenom: value.prenom.trim(),
        email: value.email.trim(),
        password: value.password,
        password_confirmation: value.password_confirmation,
        role: value.role,
        telephone: value.telephone.trim() || null,
      })
      .subscribe({
        next: () => {
          this.loading.set(false);
          this.toast.success('Bienvenue chez Accommity. Votre compte est créé.');
          void this.router.navigate([value.role === 'hote' ? '/profil/verification-identite' : '/']);
        },
        error: (err: ApiError) => {
          this.loading.set(false);
          this.error.set(err.message);
          this.applyServerErrors(err);
        },
      });
  }

  private applyServerErrors(err: ApiError): void {
    for (const [field, messages] of Object.entries(err.errors ?? {})) {
      const control = this.form.get(field);

      if (control) {
        control.setErrors({ server: messages[0] });
      }
    }
  }
}
