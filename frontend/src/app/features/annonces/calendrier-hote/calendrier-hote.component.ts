import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { AnnonceHote, BlocageCalendrier, CalendrierHote } from '../../../shared/models/annonce.model';
import { AnnonceService } from '../services/annonce.service';

type ModeCalendrier = 'bloquer' | 'debloquer';
type EtatJour = 'passe' | 'reserve' | 'bloque' | 'selection' | 'disponible' | 'hors-mois';

interface MoisAffiche {
  label: string;
  cellules: Array<string | null>;
}

@Component({
  selector: 'app-calendrier-hote',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './calendrier-hote.component.html',
  styleUrl: './calendrier-hote.component.scss',
})
export class CalendrierHoteComponent implements OnInit {
  private readonly annonces = inject(AnnonceService);
  private readonly toast = inject(ToastService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  readonly annonce = signal<AnnonceHote | null>(null);
  readonly calendrier = signal<CalendrierHote | null>(null);
  readonly loading = signal(true);
  readonly saving = signal(false);
  readonly mode = signal<ModeCalendrier>('bloquer');
  readonly moisAffiche = signal(this.premierJourDuMois(new Date()));
  readonly debut = signal<string | null>(null);
  readonly fin = signal<string | null>(null);

  readonly mois = computed<MoisAffiche[]>(() => {
    const base = this.moisAffiche();
    return [this.construireMois(base, 0), this.construireMois(base, 1)];
  });

  readonly nombreJours = computed(() => {
    const debut = this.debut();
    const fin = this.fin();
    if (!debut || !fin) {
      return 0;
    }
    return this.differenceEnJours(debut, fin) + 1;
  });

  readonly peutValider = computed(() => {
    if (!this.debut() || !this.fin() || this.saving()) {
      return false;
    }

    return this.mode() === 'bloquer' ? true : this.plageContientBlocage();
  });

  private annonceId = '';
  private readonly aujourdhui = this.cle(new Date());

  ngOnInit(): void {
    this.annonceId = this.route.snapshot.paramMap.get('id') ?? '';

    if (!this.annonceId) {
      this.router.navigate(['/hote/annonces']);
      return;
    }

    this.annonces.getAnnonceHote(this.annonceId).subscribe({
      next: (annonce) => this.annonce.set(annonce),
      error: () => {
        this.toast.error('Annonce introuvable ou accès refusé.');
        this.router.navigate(['/hote/annonces']);
      },
    });

    this.chargerCalendrier();
  }

  changerMode(mode: ModeCalendrier): void {
    this.mode.set(mode);
    this.reinitialiserSelection();
  }

  moisPrecedent(): void {
    this.moisAffiche.set(this.ajouterMois(this.moisAffiche(), -1));
    this.reinitialiserSelection();
    this.chargerCalendrier();
  }

  moisSuivant(): void {
    this.moisAffiche.set(this.ajouterMois(this.moisAffiche(), 1));
    this.reinitialiserSelection();
    this.chargerCalendrier();
  }

  etatJour(date: string | null): EtatJour {
    if (!date) {
      return 'hors-mois';
    }

    const calendrier = this.calendrier();

    if (date < this.aujourdhui) {
      return 'passe';
    }

    if (calendrier?.dates_reservees.includes(date)) {
      return 'reserve';
    }

    if (this.estDansSelection(date)) {
      return 'selection';
    }

    if (this.blocageContenant(date)) {
      return 'bloque';
    }

    return 'disponible';
  }

  selectionnerJour(date: string): void {
    const etat = this.etatJour(date);

    if (etat === 'passe' || etat === 'reserve') {
      return;
    }

    if (this.mode() === 'debloquer') {
      const blocage = this.blocageContenant(date);

      if (!blocage) {
        this.toast.info('Choisissez un jour déjà bloqué pour le débloquer.');
        return;
      }

      this.debut.set(blocage.date_debut);
      this.fin.set(blocage.date_fin);
      return;
    }

    const debut = this.debut();
    const fin = this.fin();

    if (!debut || (debut && fin)) {
      this.debut.set(date);
      this.fin.set(date);
      return;
    }

    if (date < debut) {
      this.debut.set(date);
    } else {
      this.fin.set(date);
    }
  }

  appliquer(): void {
    const debut = this.debut();
    const fin = this.fin();

    if (!debut || !fin || !this.peutValider()) {
      return;
    }

    this.saving.set(true);

    const requete: Observable<unknown> =
      this.mode() === 'bloquer'
        ? this.annonces.bloquerDates(this.annonceId, debut, fin)
        : this.annonces.debloquerDates(this.annonceId, debut, fin);

    requete.subscribe({
      next: () => {
        this.saving.set(false);
        this.toast.success(
          this.mode() === 'bloquer'
            ? 'Les dates ont été bloquées.'
            : 'Les dates ont été débloquées.',
        );
        this.reinitialiserSelection();
        this.chargerCalendrier();
      },
      error: (error: unknown) => {
        this.saving.set(false);
        this.toast.error(
          error instanceof ApiError ? error.message : 'L’opération sur le calendrier a échoué.',
        );
      },
    });
  }

  reinitialiserSelection(): void {
    this.debut.set(null);
    this.fin.set(null);
  }

  jourLisible(date: string): string {
    return new Date(`${date}T12:00:00`).toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  formatCourt(date: string): string {
    return new Date(`${date}T12:00:00`).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
    });
  }

  private chargerCalendrier(): void {
    const base = this.moisAffiche();
    const debut = this.cle(base);
    const fin = this.cle(new Date(base.getFullYear(), base.getMonth() + 2, 0));

    this.loading.set(true);

    this.annonces.getCalendrierHote(this.annonceId, debut, fin).subscribe({
      next: (calendrier) => {
        this.calendrier.set(calendrier);
        this.loading.set(false);
      },
      error: (error: unknown) => {
        this.loading.set(false);
        this.toast.error(
          error instanceof ApiError ? error.message : 'Impossible de charger le calendrier.',
        );
      },
    });
  }

  private blocageContenant(date: string): BlocageCalendrier | null {
    return (
      this.calendrier()?.blocages.find(
        (blocage) => date >= blocage.date_debut && date <= blocage.date_fin,
      ) ?? null
    );
  }

  private plageContientBlocage(): boolean {
    const debut = this.debut();
    const fin = this.fin();

    if (!debut || !fin) {
      return false;
    }

    return (this.calendrier()?.blocages ?? []).some(
      (blocage) => blocage.date_debut <= fin && blocage.date_fin >= debut,
    );
  }

  private estDansSelection(date: string): boolean {
    const debut = this.debut();
    const fin = this.fin();

    if (!debut || !fin) {
      return false;
    }

    return date >= debut && date <= fin;
  }

  private construireMois(base: Date, decalage: number): MoisAffiche {
    const premier = new Date(base.getFullYear(), base.getMonth() + decalage, 1);
    const label = premier.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    const nombreJours = new Date(premier.getFullYear(), premier.getMonth() + 1, 0).getDate();
    const decalageSemaine = (premier.getDay() + 6) % 7; // lundi = 0

    const cellules: Array<string | null> = Array.from({ length: decalageSemaine }, () => null);

    for (let jour = 1; jour <= nombreJours; jour++) {
      cellules.push(this.cle(new Date(premier.getFullYear(), premier.getMonth(), jour)));
    }

    while (cellules.length % 7 !== 0) {
      cellules.push(null);
    }

    return { label: label.charAt(0).toUpperCase() + label.slice(1), cellules };
  }

  private cle(date: Date): string {
    const annee = date.getFullYear();
    const mois = String(date.getMonth() + 1).padStart(2, '0');
    const jour = String(date.getDate()).padStart(2, '0');
    return `${annee}-${mois}-${jour}`;
  }

  private premierJourDuMois(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), 1);
  }

  private ajouterMois(date: Date, decalage: number): Date {
    return new Date(date.getFullYear(), date.getMonth() + decalage, 1);
  }

  private differenceEnJours(debut: string, fin: string): number {
    const d1 = new Date(`${debut}T12:00:00`).getTime();
    const d2 = new Date(`${fin}T12:00:00`).getTime();
    return Math.round((d2 - d1) / 86_400_000);
  }

  numeroJour(date: string): string {
    return String(Number(date.slice(8, 10)));
  }
}
