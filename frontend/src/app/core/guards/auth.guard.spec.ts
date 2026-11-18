import { TestBed } from '@angular/core/testing';
import { Router, ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree } from '@angular/router';
import { authGuard } from './auth.guard';
import { AuthService } from '../services/auth.service';

describe('authGuard', () => {
  let authServiceSpy: jasmine.SpyObj<AuthService>;
  let routerSpy: jasmine.SpyObj<Router>;

  beforeEach(() => {
    authServiceSpy = jasmine.createSpyObj('AuthService', ['isAuthenticated']);
    routerSpy = jasmine.createSpyObj('Router', ['createUrlTree']);

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authServiceSpy },
        { provide: Router, useValue: routerSpy }
      ]
    });
  });

  it('allows access when user is authenticated', () => {
    authServiceSpy.isAuthenticated.and.returnValue(true);

    const dummyRoute = {} as ActivatedRouteSnapshot;
    const dummyState = { url: '/profil' } as RouterStateSnapshot;

    const result = TestBed.runInInjectionContext(() => authGuard(dummyRoute, dummyState));
    expect(result).toBeTrue();
  });

  it('redirects to /auth/login with returnUrl when unauthenticated', () => {
    authServiceSpy.isAuthenticated.and.returnValue(false);
    const expectedTree = {} as UrlTree;
    routerSpy.createUrlTree.and.returnValue(expectedTree);

    const dummyRoute = {} as ActivatedRouteSnapshot;
    const dummyState = { url: '/profil' } as RouterStateSnapshot;

    const result = TestBed.runInInjectionContext(() => authGuard(dummyRoute, dummyState));
    expect(result).toBe(expectedTree);
    expect(routerSpy.createUrlTree).toHaveBeenCalledWith(['/auth/login'], {
      queryParams: { returnUrl: '/profil' }
    });
  });
});
