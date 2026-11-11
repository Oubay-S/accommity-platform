import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const hoteGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.isHoteVerifie()) {
    return true;
  }

  router.navigate(['/profil/verification-identite']);
  return false;
};
