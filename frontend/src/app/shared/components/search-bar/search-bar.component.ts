import { Component, Input, OnChanges, SimpleChanges, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { CriteresRecherche } from '../../models/annonce.model';

@Component({
  selector: 'app-search-bar',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './search-bar.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
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
  /** Date du jour en heure locale (toISOString() est en UTC). */
  readonly today = SearchBarComponent.localIso(new Date());

  private static localIso(d: Date): string {
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  }

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

    // null = paramètre retiré de l'URL lors d'une fusion
    const queryParams: Record<string, string | number | null> = {
      ville: this.ville.trim() || null,
      date_arrivee: this.date_arrivee || null,
      date_depart: this.date_depart || null,
      voyageurs: this.voyageurs && this.voyageurs >= 1 ? this.voyageurs : null,
    };

    if (this.compact) {
      // Page résultats : on garde les filtres latéraux (prix, type, équipements, tri)
      // et on revient à la première page.
      this.router.navigate(['/annonces'], {
        queryParams: { ...queryParams, page: null },
        queryParamsHandling: 'merge',
      });
      return;
    }

    this.router.navigate(['/annonces'], { queryParams });
  }
}
