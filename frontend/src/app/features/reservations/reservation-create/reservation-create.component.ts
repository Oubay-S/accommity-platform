import { Component, OnInit, inject, signal, computed, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AnnonceService } from '../../annonces/services/annonce.service';
import { ReservationService } from '../services/reservation.service';
import { AnnonceDetail } from '../../../shared/models/annonce.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';
import { ApiError } from '../../../shared/models/api-response.model';

@Component({
  selector: 'app-reservation-create',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, CurrencyMadPipe],
  templateUrl: './reservation-create.component.html',
  styleUrl: './reservation-create.component.scss',
  changeDetection: ChangeDetectionStrategy.Default
})
export class ReservationCreateComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly annonceService = inject(AnnonceService);
  private readonly reservationService = inject(ReservationService);

  readonly annonce = signal<AnnonceDetail | null>(null);
  readonly chargement = signal(true);
  readonly envoiEnCours = signal(false);
  readonly erreur = signal<string | null>(null);

  readonly dateArrivee = signal<string>('');
  readonly dateDepart = signal<string>('');
  readonly nbVoyageurs = signal<number>(1);
  readonly messageOptionnel = signal<string>('');

  readonly minDate = new Date().toISOString().split('T')[0];

  readonly nbNuits = computed(() => {
    const arr = this.dateArrivee();
    const dep = this.dateDepart();
    if (!arr || !dep) return 0;
    const diffTime = new Date(dep).getTime() - new Date(arr).getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
  });

  readonly tarifNuit = computed(() => {
    const ann = this.annonce();
    if (!ann) return 0;
    return typeof ann.tarif_nuit === 'string' ? parseFloat(ann.tarif_nuit) : ann.tarif_nuit;
  });

  readonly sousTotal = computed(() => {
    return this.nbNuits() * this.tarifNuit();
  });

  readonly fraisService = computed(() => {
    return Math.round(this.sousTotal() * 0.12 * 100) / 100;
  });

  readonly montantTotal = computed(() => {
    return this.sousTotal() + this.fraisService();
  });

  readonly datesValides = computed(() => {
    return this.nbNuits() > 0;
  });

  readonly peutSoumettre = computed(() => {
    const ann = this.annonce();
    if (!ann || this.envoiEnCours()) return false;
    return (
      this.datesValides() &&
      this.nbVoyageurs() >= 1 &&
      this.nbVoyageurs() <= ann.capacite
    );
  });

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('annonceId');
    if (!id) {
      this.router.navigate(['/annonces']);
      return;
    }

    // Pré-remplissage via query params si présents
    const qp = this.route.snapshot.queryParams;
    if (qp['date_arrivee']) this.dateArrivee.set(qp['date_arrivee']);
    if (qp['date_depart']) this.dateDepart.set(qp['date_depart']);
    if (qp['voyageurs']) this.nbVoyageurs.set(Number(qp['voyageurs']) || 1);

    this.annonceService.getAnnonce(id).subscribe({
      next: (ann) => {
        this.annonce.set(ann);
        this.chargement.set(false);
      },
      error: () => {
        this.erreur.set('Impossible de charger les détails du logement.');
        this.chargement.set(false);
      }
    });
  }

  soumettre(): void {
    const ann = this.annonce();
    if (!ann || !this.peutSoumettre()) return;

    this.envoiEnCours.set(true);
    this.erreur.set(null);

    this.reservationService.creerReservation({
      id_annonce: ann.id_annonce,
      date_arrivee: this.dateArrivee(),
      date_depart: this.dateDepart(),
      nb_voyageurs: this.nbVoyageurs(),
      message_optionnel: this.messageOptionnel().trim() || undefined
    }).subscribe({
      next: (reservation) => {
        this.envoiEnCours.set(false);
        if (reservation.mode_reservation === 'instantanee') {
          this.router.navigate(['/paiements/checkout', reservation.id_reservation]);
        } else {
          this.router.navigate(['/reservations/mes-voyages'], {
            queryParams: { demande_envoyee: 'true' }
          });
        }
      },
      error: (err: ApiError) => {
        this.envoiEnCours.set(false);
        if (err.status === 409) {
          this.erreur.set('Ce logement est déjà réservé ou indisponible pour ces dates. Veuillez choisir une autre période.');
        } else if (err.status === 403) {
          this.erreur.set(err.message || 'Action non autorisée. Vous ne pouvez pas réserver votre propre hébergement.');
        } else {
          this.erreur.set(err.message || 'Une erreur est survenue lors de la création de la réservation.');
        }
      }
    });
  }
}
