import {
  ChangeDetectionStrategy,
  Component,
  Input,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { RatingStarsComponent } from '../../../shared/components/rating-stars/rating-stars.component';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { Evaluation, EvaluationPage } from '../../../shared/models/evaluation.model';
import { EvaluationService } from '../services/evaluation.service';

interface RepartitionLigne {
  etoile: number;
  nombre: number;
  pourcentage: number;
}

@Component({
  selector: 'app-evaluation-list',
  standalone: true,
  imports: [FormsModule, RouterLink, RatingStarsComponent],
  templateUrl: './evaluation-list.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './evaluation-list.component.scss',
})
export class EvaluationListComponent implements OnInit {
  @Input({ required: true }) annonceId!: string;

  private readonly evaluationService = inject(EvaluationService);
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);

  readonly page = signal<EvaluationPage | null>(null);
  readonly loading = signal(true);
  readonly erreur = signal<string | null>(null);
  readonly connecte = computed(() => this.auth.isAuthenticated());

  readonly signalerCible = signal<Evaluation | null>(null);
  readonly motif = signal('');
  readonly signalementEnCours = signal(false);
  readonly signalementErreur = signal<string | null>(null);

  readonly repartition = computed<RepartitionLigne[]>(() => {
    const stats = this.page()?.statistiques;

    if (!stats) {
      return [];
    }

    return [5, 4, 3, 2, 1].map((etoile) => {
      const nombre = Number(stats.repartition[String(etoile)] ?? 0);
      const pourcentage = stats.nb_avis === 0 ? 0 : Math.round((nombre / stats.nb_avis) * 100);

      return { etoile, nombre, pourcentage };
    });
  });

  ngOnInit(): void {
    this.charger(1);
  }

  charger(page: number): void {
    this.loading.set(true);
    this.erreur.set(null);

    this.evaluationService.listerPourAnnonce(this.annonceId, { page, per_page: 6 }).subscribe({
      next: (reponse) => {
        this.page.set(reponse);
        this.loading.set(false);
      },
      error: (erreur: unknown) => {
        this.erreur.set(
          erreur instanceof ApiError ? erreur.message : 'Impossible de charger les avis.',
        );
        this.loading.set(false);
      },
    });
  }

  peutSignaler(evaluation: Evaluation): boolean {
    const utilisateur = this.auth.currentUser();

    return this.connecte() && utilisateur !== null && evaluation.auteur?.id_utilisateur !== utilisateur.id_utilisateur;
  }

  initiale(evaluation: Evaluation): string {
    return (evaluation.auteur?.prenom || evaluation.auteur?.nom || '?').charAt(0).toUpperCase();
  }

  dateLisible(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  notesDetaillees(evaluation: Evaluation): string[] {
    const notes = evaluation.notes_detaillees;

    if (!notes) {
      return [];
    }

    return [
      `Propreté ${notes.proprete}/5`,
      `Communication ${notes.communication}/5`,
      `Emplacement ${notes.emplacement}/5`,
      `Qualité/prix ${notes.rapport_qualite_prix}/5`,
      `Exactitude ${notes.exactitude}/5`,
    ];
  }

  ouvrirSignalement(evaluation: Evaluation): void {
    this.signalerCible.set(evaluation);
    this.motif.set('');
    this.signalementErreur.set(null);
  }

  fermerSignalement(): void {
    this.signalerCible.set(null);
    this.motif.set('');
    this.signalementErreur.set(null);
  }

  confirmerSignalement(): void {
    const evaluation = this.signalerCible();

    if (!evaluation || this.motif().trim().length < 10) {
      this.signalementErreur.set('Le motif doit contenir au moins 10 caractères.');
      return;
    }

    this.signalementEnCours.set(true);
    this.signalementErreur.set(null);

    this.evaluationService.signaler(evaluation.id_evaluation, { motif: this.motif().trim() }).subscribe({
      next: () => {
        this.signalementEnCours.set(false);
        this.toast.success('Merci, votre signalement a bien été transmis.');
        this.fermerSignalement();
        this.charger(this.page()?.pagination.current_page ?? 1);
      },
      error: (erreur: unknown) => {
        this.signalementEnCours.set(false);
        this.signalementErreur.set(
          erreur instanceof ApiError ? erreur.message : 'Impossible de signaler cet avis.',
        );
      },
    });
  }

}
