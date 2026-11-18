import { TestBed } from '@angular/core/testing';
import { TokenService } from './token.service';

describe('TokenService', () => {
  let service: TokenService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(TokenService);
    localStorage.clear();
  });

  afterEach(() => {
    localStorage.clear();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should save and retrieve token', () => {
    service.saveToken('test-token-123');
    expect(service.getToken()).toBe('test-token-123');
    expect(service.hasToken()).toBeTrue();
  });

  it('should remove token', () => {
    service.saveToken('test-token-123');
    service.removeToken();
    expect(service.getToken()).toBeNull();
    expect(service.hasToken()).toBeFalse();
  });
});
