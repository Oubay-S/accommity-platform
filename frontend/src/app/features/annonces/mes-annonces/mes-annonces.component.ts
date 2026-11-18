import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { AnnonceHote, MesAnnoncesResultat, StatutAnnonce } from '../../../shared/models/annonce.model';
import { AnnonceService } from '../services/annonce.service';

@Component({
  selector: 'app-mes-annonces',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './mes-annonces.component.html',
  styleUrl: './mes-annonces.component.scss',
})
export class MesAnnoncesComponent implements OnInit {
  private readonly annonces = inject(AnnonceService);
  private readonly toast = inject(ToastService);

  readonly resultat = signal<MesAnnoncesResultat | null>(null);
  readonly loading = signal(true);
  readonly statut = signal<StatutAnnonce | ''>('');
  readonly recherche = signal('');
  readonly page = signal(1);
  readonly aSupprimer = signal<AnnonceHote | null>(null);
  readonly suppressionEnCours = signal(false);

  readonly onglets: Array<{ valeur: StatutAnnonce | ''; libelle: string; cle: string }> = [
    { valeur: '', libelle: 'Toutes', cle: 'total' },
    { valeur: 'en_attente', libelle: 'En attente', cle: 'en_attente' },
    { valeur: 'publiee', libelle: 'Publiées', cle: 'publiee' },
    { valeur: 'suspendue', libelle: 'Suspendues', cle: 'suspendue' },
    { valeur: 'rejetee', libelle: 'Rejetées', cle: 'rejetee' },
    { valeur: 'desactivee', libelle: 'Désactivées', cle: 'desactivee' },
  ];

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.loading.set(true);

    this.annonces
      .getMesAnnonces({
        statut: this.statut() || undefined,
        recherche: this.recherche() || undefined,
        page: this.page(),
        per_page: 9,
      })
      .subscribe({
        next: (resultat) => {
          this.resultat.set(resultat);
          this.loading.set(false);
        },
        error: (error: unknown) => {
          this.loading.set(false);
          this.toast.error(
            error instanceof ApiError ? error.message : 'Impossible de charger vos annonces.',
          );
        },
      });
  }

  changerStatut(valeur: StatutAnnonce | ''): void {
    this.statut.set(valeur);
    this.page.set(1);
    this.charger();
  }

  rechercher(): void {
    this.page.set(1);
    this.charger();
  }

  changerPage(page: number): void {
    this.page.set(page);
    this.charger();
  }

  compteur(cle: string): number {
    return Number((this.resultat()?.statistiques as unknown as Record<string, number>)?.[cle] ?? 0);
  }

  libelleStatut(statut: StatutAnnonce): string {
    switch (statut) {
      case 'publiee':
        return 'Publiée';
      case 'en_attente':
        return 'En attente';
      case 'suspendue':
        return 'Suspendue';
      case 'rejetee':
        return 'Rejetée';
      case 'desactivee':
        return 'Désactivée';
      default:
        return statut;
    }
  }

  demanderSuppression(annonce: AnnonceHote): void {
    this.aSupprimer.set(annonce);
  }

  annulerSuppression(): void {
    this.aSupprimer.set(null);
  }

  confirmerSuppression(): void {
    const annonce = this.aSupprimer();
    if (!annonce) {
      return;
    }

    this.suppressionEnCours.set(true);

    this.annonces.supprimerAnnonce(annonce.id_annonce).subscribe({
      next: () => {
        this.suppressionEnCours.set(false);
        this.aSupprimer.set(null);
        this.toast.success('Annonce supprimée.');
        this.charger();
      },
      error: (error: unknown) => {
        this.suppressionEnCours.set(false);
        this.aSupprimer.set(null);
        this.toast.error(
          error instanceof ApiError ? error.message : 'La suppression de l’annonce a échoué.',
        );
      },
    });
  }
}
