import { Component, OnInit, inject, signal, computed, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ReservationService } from '../../reservations/services/reservation.service';
import { PaiementService } from '../services/paiement.service';
import { Reservation } from '../../../shared/models/reservation.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';
import { ApiError } from '../../../shared/models/api-response.model';

@Component({
  selector: 'app-checkout',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, CurrencyMadPipe],
  templateUrl: './checkout.component.html',
  styleUrl: './checkout.component.scss',
  changeDetection: ChangeDetectionStrategy.Default
})
export class CheckoutComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly reservationService = inject(ReservationService);
  private readonly paiementService = inject(PaiementService);

  readonly reservation = signal<Reservation | null>(null);
  readonly chargement = signal(true);
  readonly paiementEnCours = signal(false);
  readonly erreur = signal<string | null>(null);

  // Formulaire Carte Bancaire
  readonly nomTitulaire = signal('Taha Asrar');
  readonly numeroCarte = signal('4242 4242 4242 4242');
  readonly dateExpiration = signal('12/28');
  readonly cvv = signal('123');

  readonly formulaireValide = computed(() => {
    // Mode simulation ouvert : pas de formule imposée, tout numéro/formulaire est accepté
    return !this.paiementEnCours();
  });

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('reservationId');
    if (!id) {
      this.router.navigate(['/reservations/mes-voyages']);
      return;
    }

    this.reservationService.getReservation(id).subscribe({
      next: (res) => {
        // Si déjà payée
        if (res.paiement && res.paiement.statut === 'reussi') {
          this.router.navigate(['/paiements/recu', res.paiement.id_paiement]);
          return;
        }

        this.reservation.set(res);
        this.chargement.set(false);
      },
      error: () => {
        this.erreur.set('Impossible de charger la réservation à régler.');
        this.chargement.set(false);
      }
    });
  }

  remplirCarteTest(): void {
    this.nomTitulaire.set('Taha Asrar');
    this.numeroCarte.set('4242 4242 4242 4242');
    this.dateExpiration.set('12/28');
    this.cvv.set('123');
    this.erreur.set(null);
  }

  payer(): void {
    const res = this.reservation();
    if (!res || !this.formulaireValide()) return;

    this.paiementEnCours.set(true);
    this.erreur.set(null);

    this.paiementService.payer(res.id_reservation, {
      nom_titulaire: this.nomTitulaire(),
      numero_carte: this.numeroCarte().replace(/\s+/g, ''),
      date_expiration: this.dateExpiration(),
      cvv: this.cvv()
    }).subscribe({
      next: (resultat) => {
        this.paiementEnCours.set(false);
        this.router.navigate(['/paiements/recu', resultat.id_paiement], {
          queryParams: { paiement_reussi: 'true' }
        });
      },
      error: (err: ApiError) => {
        this.paiementEnCours.set(false);
        if (err.status === 402) {
          this.erreur.set('Transaction refusée par la banque émettrice. Veuillez vérifier les coordonnées ou utiliser la carte test 4242 4242 4242 4242.');
        } else if (err.status === 409) {
          this.erreur.set('Cette réservation a déjà été payée ou n\'est plus en état d\'être réglée.');
        } else {
          this.erreur.set(err.message || 'Une erreur est survenue lors du paiement.');
        }
      }
    });
  }
}
