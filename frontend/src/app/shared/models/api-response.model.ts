export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
  error_code?: string;
}

export interface Pagination {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface PaginatedData<T> {
  items: T[];
  pagination: Pagination;
}

/**
 * Erreur normalisée par l'ErrorInterceptor : les composants n'ont plus à
 * manipuler HttpErrorResponse.
 */
export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly errors: Record<string, string[]> | null = null,
    public readonly errorCode: string | null = null,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  fieldError(field: string): string | null {
    return this.errors?.[field]?.[0] ?? null;
  }
}
