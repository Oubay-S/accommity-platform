import { Injectable, signal } from '@angular/core';

export type ToastType = 'success' | 'error' | 'info';

export interface Toast {
  id: number;
  type: ToastType;
  message: string;
}

/**
 * Notifications "toast" uniformes (promesse de la convention API).
 */
@Injectable({ providedIn: 'root' })
export class ToastService {
  private readonly _toasts = signal<Toast[]>([]);
  private sequence = 0;

  readonly toasts = this._toasts.asReadonly();

  success(message: string, ttl = 4000): void {
    this.show(message, 'success', ttl);
  }

  error(message: string, ttl = 5500): void {
    this.show(message, 'error', ttl);
  }

  info(message: string, ttl = 4000): void {
    this.show(message, 'info', ttl);
  }

  dismiss(id: number): void {
    this._toasts.update((list) => list.filter((toast) => toast.id !== id));
  }

  private show(message: string, type: ToastType, ttl: number): void {
    const id = ++this.sequence;
    this._toasts.update((list) => [...list, { id, type, message }]);
    window.setTimeout(() => this.dismiss(id), ttl);
  }
}
