import { Component, OnInit, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { AnnonceCardComponent } from '../../shared/components/annonce-card/annonce-card.component';
import { SearchBarComponent } from '../../shared/components/search-bar/search-bar.component';
import { AnnonceResume } from '../../shared/models/annonce.model';
import { AnnonceService } from '../annonces/services/annonce.service';

interface TypeLogementItem {
  value: string;
  label: string;
  icon: string;
}

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [RouterLink, SearchBarComponent, AnnonceCardComponent],
  templateUrl: './home.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './home.component.scss',
})
export class HomeComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly annonceService = inject(AnnonceService);

  readonly isAuthenticated = this.auth.isAuthenticated;
  readonly isHote = this.auth.isHote;

  readonly enVedette = signal<AnnonceResume[]>([]);
  readonly loading = signal(true);
  readonly skeletons = [1, 2, 3, 4, 5, 6];

  readonly typesLogement: TypeLogementItem[] = [
    { value: 'riad', label: 'Riads', icon: 'mosque' },
    { value: 'villa', label: 'Villas', icon: 'villa' },
    { value: 'appartement', label: 'Appartements', icon: 'apartment' },
    { value: 'maison', label: 'Maisons', icon: 'home' },
    { value: 'studio', label: 'Studios', icon: 'meeting_room' },
    { value: 'chambre', label: 'Chambres', icon: 'bed' },
  ];

  ngOnInit(): void {
    this.annonceService.getEnVedette().subscribe({
      next: (items) => {
        this.enVedette.set(items ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.enVedette.set([]);
        this.loading.set(false);
      },
    });
  }
}
