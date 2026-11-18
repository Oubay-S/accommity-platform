import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { FormBuilder, FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { EvaluationService } from '../services/evaluation.service';
import { StarRatingInputComponent } from '../star-rating-input/star-rating-input.component';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import {
  EvaluationCreatePayload,
  NotesDetaillees,
  ReservationAEvaluer,
} from '../../../shared/models/evaluation.model';

interface Critere {
  cle: keyof NotesDetaillees;
  libelle: string;
}

@Component({
  selector: 'app-evaluation-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, StarRatingInputComponent],
  templateUrl: './evaluation-form.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './evaluation-form.component.scss',
})
export class EvaluationFormComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly evaluationService = inject(EvaluationService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly fb = inject(FormBuilder);

  readonly criteres: Critere[] = [
    { cle: 'proprete', libelle: 'Propreté' },
    { cle: 'communication', libelle: 'Communication' },
    { cle: 'emplacement', libelle: 'Emplacement' },
    { cle: 'rapport_qualite_prix', libelle: 'Rapport qualité / prix' },
    { cle: 'exactitude', libelle: 'Exactitude de l’annonce' },
  ];

  readonly form = this.fb.group({
    note: [0, [Validators.min(1), Validators.max(5)]],
    commentaire: ['', [Validators.required, Validators.minLength(10), Validators.maxLength(2000)]],
    notes: this.fb.group({
      proprete: [0],
      communication: [0],
      emplacement: [0],
      rapport_qualite_prix: [0],
      exactitude: [0],
    }),
  });

  readonly reservation = signal<ReservationAEvaluer | null>(null);
  readonly loading = signal(true);
  readonly loadError = signal<string | null>(null);
  readonly submitting = signal(false);
  readonly serverError = signal<string | null>(null);

  readonly estVoyageur = computed(() => this.reservation()?.role === 'voyageur');

  ngOnInit(): void {
    const reservationId = this.route.snapshot.paramMap.get('reservationId');

    if (!reservationId) {
      this.loading.set(false);
      this.loadError.set('Réservation introuvable.');
      return;
    }

    this.evaluationService
      .aEvaluer()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: ({ items }) => {
          const reservation = items.find((item) => item.id_reservation === reservationId) ?? null;

          if (!reservation) {
            this.loadError.set('Ce séjour n’est pas éligible à une évaluation.');
          } else {
            this.reservation.set(reservation);
            this.configurerValidateurs(reservation);
          }

          this.loading.set(false);
        },
        error: (erreur: unknown) => {
          this.loadError.set(
            erreur instanceof ApiError ? erreur.message : 'Impossible de charger le séjour.',
          );
          this.loading.set(false);
        },
      });
  }

  get noteControl(): FormControl<number> {
    return this.form.controls.note as FormControl<number>;
  }

  get commentaireControl(): FormControl<string> {
    return this.form.controls.commentaire as FormControl<string>;
  }

  get notesGroup(): FormGroup {
    return this.form.controls.notes as FormGroup;
  }

  setNote(value: number): void {
    this.noteControl.setValue(value);
    this.noteControl.markAsTouched();
  }

  setCritere(cle: keyof NotesDetaillees, value: number): void {
    const control = this.notesGroup.get(cle);
    control?.setValue(value);
    control?.markAsTouched();
  }

  valeurCritere(cle: keyof NotesDetaillees): number {
    return Number(this.notesGroup.get(cle)?.value ?? 0);
  }

  champInvalide(nom: string): boolean {
    const control = this.form.get(nom);
    return control !== null && control.invalid && control.touched;
  }

  soumettre(): void {
    this.serverError.set(null);
    this.form.markAllAsTouched();

    if (this.form.invalid) {
      return;
    }

    const reservation = this.reservation();

    if (!reservation) {
      return;
    }

    const payload: EvaluationCreatePayload = {
      id_reservation: reservation.id_reservation,
      note: Number(this.noteControl.value),
      commentaire: this.commentaireControl.value.trim(),
    };

    if (reservation.role === 'voyageur') {
      payload.notes_detaillees = {
        proprete: this.valeurCritere('proprete'),
        communication: this.valeurCritere('communication'),
        emplacement: this.valeurCritere('emplacement'),
        rapport_qualite_prix: this.valeurCritere('rapport_qualite_prix'),
        exactitude: this.valeurCritere('exactitude'),
      };
    }

    this.submitting.set(true);

    this.evaluationService
      .creer(payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.submitting.set(false);
          this.toast.success('Votre avis a été publié.');
          const annonceId = reservation.annonce?.id_annonce;
          void this.router.navigate(annonceId ? ['/annonces', annonceId] : ['/profil/evaluations']);
        },
        error: (erreur: unknown) => {
          this.submitting.set(false);
          this.serverError.set(
            erreur instanceof ApiError ? erreur.message : 'Impossible de publier votre avis.',
          );
        },
      });
  }

  private configurerValidateurs(reservation: ReservationAEvaluer): void {
    const notes = this.notesGroup.controls;

    for (const critere of this.criteres) {
      const control = notes[critere.cle];

      if (!control) {
        continue;
      }

      if (reservation.role === 'voyageur') {
        control.setValidators([Validators.min(1), Validators.max(5)]);
      } else {
        control.clearValidators();
        control.setValue(0);
      }

      control.updateValueAndValidity();
    }
  }
}
