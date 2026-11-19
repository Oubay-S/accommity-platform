import { Component, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { filter } from 'rxjs';
import { HeaderComponent } from './shared/components/header/header.component';
import { FooterComponent } from './shared/components/footer/footer.component';
import { ToastComponent } from './shared/components/toast/toast.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, HeaderComponent, FooterComponent, ToastComponent],
  templateUrl: './app.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './app.component.scss'
})
export class AppComponent {
  private readonly router = inject(Router);

  /** Les pages d'authentification et le portail admin sont plein écran. */
  readonly showChrome = signal(!this.isBare(this.router.url));

  constructor() {
    this.router.events
      .pipe(
        filter((event): event is NavigationEnd => event instanceof NavigationEnd),
        takeUntilDestroyed(),
      )
      .subscribe(() => this.showChrome.set(!this.isBare(this.router.url)));
  }

  private isBare(url: string): boolean {
    return url.startsWith('/auth') || url.startsWith('/admin');
  }
}
