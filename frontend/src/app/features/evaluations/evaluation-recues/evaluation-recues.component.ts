import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { RatingStarsComponent } from '../../../shared/components/rating-stars/rating-stars.component';
import { ApiError } from '../../../shared/models/api-response.model';
import { EvaluationPage } from '../../../shared/models/evaluation.model';
import { EvaluationService } from '../services/evaluation.service';

@Component({
  selector: 'app-evaluation-recues',
  standalone: true,
  imports: [RouterLink, RatingStarsComponent],
  templateUrl: './evaluation-recues.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './evaluation-recues.component.scss',
})
export class EvaluationRecuesComponent implements OnInit {
  private readonly evaluationService = inject(EvaluationService);

  readonly page = signal<EvaluationPage | null>(null);
  readonly loading = signal(true);
  readonly erreur = signal<string | null>(null);

  ngOnInit(): void {
    this.evaluationService.recues({ per_page: 3 }).subscribe({
      next: (reponse) => {
        this.page.set(reponse);
        this.loading.set(false);
      },
      error: (erreur: unknown) => {
        this.erreur.set(
          erreur instanceof ApiError ? erreur.message : 'Impossible de charger vos avis.',
        );
        this.loading.set(false);
      },
    });
  }

  dateLisible(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
  }
}
