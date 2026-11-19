import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { RatingStarsComponent } from '../../../shared/components/rating-stars/rating-stars.component';
import { ApiError } from '../../../shared/models/api-response.model';
import {
  EvaluationDonneesPage,
  ReservationAEvaluer,
} from '../../../shared/models/evaluation.model';
import { EvaluationService } from '../services/evaluation.service';

type Onglet = 'a-evaluer' | 'donnees';

@Component({
  selector: 'app-mes-evaluations',
  standalone: true,
  imports: [RouterLink, RatingStarsComponent],
  templateUrl: './mes-evaluations.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './mes-evaluations.component.scss',
})
export class MesEvaluationsComponent implements OnInit {
  private readonly evaluationService = inject(EvaluationService);

  readonly onglet = signal<Onglet>('a-evaluer');
  readonly aEvaluer = signal<ReservationAEvaluer[]>([]);
  readonly donnees = signal<EvaluationDonneesPage | null>(null);
  readonly loading = signal(true);
  readonly erreur = signal<string | null>(null);

  ngOnInit(): void {
    this.charger();
  }

  changerOnglet(onglet: Onglet): void {
    this.onglet.set(onglet);
  }

  dateLisible(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  }

  private charger(): void {
    this.loading.set(true);
    this.erreur.set(null);

    let restants = 2;

    const terminer = (): void => {
      restants -= 1;

      if (restants === 0) {
        this.loading.set(false);
      }
    };

    this.evaluationService.aEvaluer().subscribe({
      next: ({ items }) => {
        this.aEvaluer.set(items);
        terminer();
      },
      error: (erreur: unknown) => {
        this.erreur.set(
          erreur instanceof ApiError ? erreur.message : 'Impossible de charger vos évaluations.',
        );
        terminer();
      },
    });

    this.evaluationService.donnees({ per_page: 20 }).subscribe({
      next: (page) => {
        this.donnees.set(page);
        terminer();
      },
      error: () => terminer(),
    });
  }
}
