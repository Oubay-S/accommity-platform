import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly submitted = signal(false);
  readonly error = signal<string | null>(null);
  readonly showPassword = signal(false);

  readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
    remember: [true],
  });

  get email() {
    return this.form.controls.email;
  }

  get password() {
    return this.form.controls.password;
  }

  togglePassword(): void {
    this.showPassword.update((visible) => !visible);
  }

  forgotPassword(): void {
    this.toast.info('La réinitialisation par e-mail sera disponible prochainement.');
  }

  submit(): void {
    this.submitted.set(true);

    if (this.form.invalid || this.loading()) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    const { email, password } = this.form.getRawValue();

    this.auth.login({ email, password }).subscribe({
      next: () => {
        this.loading.set(false);
        this.toast.success('Connexion réussie. Bienvenue chez Accommity.');

        const requested = this.route.snapshot.queryParamMap.get('returnUrl');
        const fallback = this.auth.isAdmin() ? '/admin' : '/';
        const target = requested?.startsWith('/') && !requested.startsWith('//') ? requested : fallback;

        void this.router.navigateByUrl(target);
      },
      error: (err: ApiError) => {
        this.loading.set(false);
        this.error.set(err.message);

        const emailError = err.fieldError('email');
        const passwordError = err.fieldError('password');

        if (emailError) {
          this.email.setErrors({ server: emailError });
        }
        if (passwordError) {
          this.password.setErrors({ server: passwordError });
        }
      },
    });
  }
}
