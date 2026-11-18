import { Component, Input, OnChanges, SimpleChanges, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { CriteresRecherche } from '../../models/annonce.model';

@Component({
  selector: 'app-search-bar',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './search-bar.component.html',
  styleUrl: './search-bar.component.scss',
})
export class SearchBarComponent implements OnChanges {
  private readonly router = inject(Router);

  @Input() compact = false;
  @Input() initial?: Partial<CriteresRecherche>;

  ville = '';
  date_arrivee = '';
  date_depart = '';
  voyageurs: number | null = null;

  readonly erreur = signal<string | null>(null);
  readonly today = new Date().toISOString().slice(0, 10);

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['initial'] && this.initial) {
      this.ville = this.initial.ville ?? '';
      this.date_arrivee = this.initial.date_arrivee ?? '';
      this.date_depart = this.initial.date_depart ?? '';
      this.voyageurs = this.initial.voyageurs ?? null;
    }
  }

  onSubmit(): void {
    this.erreur.set(null);

    if ((this.date_arrivee && !this.date_depart) || (!this.date_arrivee && this.date_depart)) {
      this.erreur.set('Veuillez renseigner à la fois la date d\'arrivée et la date de départ.');
      return;
    }

    if (this.date_arrivee && this.date_arrivee < this.today) {
      this.erreur.set('La date d\'arrivée ne peut pas être dans le passé.');
      return;
    }

    if (this.date_arrivee && this.date_depart && this.date_depart <= this.date_arrivee) {
      this.erreur.set('La date de départ doit être postérieure à la date d\'arrivée.');
      return;
    }

    const queryParams: Record<string, string | number | undefined> = {};
    if (this.ville.trim()) {
      queryParams['ville'] = this.ville.trim();
    }
    if (this.date_arrivee) {
      queryParams['date_arrivee'] = this.date_arrivee;
    }
    if (this.date_depart) {
      queryParams['date_depart'] = this.date_depart;
    }
    if (this.voyageurs && this.voyageurs >= 1) {
      queryParams['voyageurs'] = this.voyageurs;
    }

    this.router.navigate(['/annonces'], { queryParams });
  }
}
