import { Component, OnInit, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ReservationService } from '../services/reservation.service';
import { Reservation } from '../../../shared/models/reservation.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';
import { PaginationComponent } from '../../../shared/components/pagination/pagination.component';
import { Pagination, ApiError } from '../../../shared/models/api-response.model';
import { AnnulationModalComponent } from '../annulation-modal/annulation-modal.component';

@Component({
  selector: 'app-reservations-hote',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, CurrencyMadPipe, PaginationComponent, AnnulationModalComponent],
  templateUrl: './reservations-hote.component.html',
  styleUrl: './reservations-hote.component.scss',
  changeDetection: ChangeDetectionStrategy.Default
})
export class ReservationsHoteComponent implements OnInit {
  private readonly reservationService = inject(ReservationService);

  readonly demandes = signal<Reservation[]>([]);
  readonly chargement = signal(true);
  readonly filtre = signal<string>('tous');
  readonly pagination = signal<Pagination | null>(null);
  readonly actionEnCours = signal<string | null>(null);

  readonly messageSucces = signal<string | null>(null);
  readonly messageErreur = signal<string | null>(null);

  // Modale de refus
  readonly demandeARefuser = signal<Reservation | null>(null);
  readonly motifRefus = signal<string>('');

  // Modale d'annulation (Hôte)
  readonly reservationAAnnuler = signal<Reservation | null>(null);

  ngOnInit(): void {
    this.chargerDemandes(1);
  }

  chargerDemandes(page = 1): void {
    this.chargement.set(true);
    this.reservationService.getDemandesHote(page).subscribe({
      next: (res) => {
        this.demandes.set(res.items);
        this.pagination.set(res.pagination);
        this.chargement.set(false);
      },
      error: () => {
        this.messageErreur.set('Impossible de récupérer les demandes de réservation.');
        this.chargement.set(false);
      }
    });
  }

  changerPage(page: number): void {
    this.chargerDemandes(page);
  }

  changerFiltre(nouveauFiltre: string): void {
    this.filtre.set(nouveauFiltre);
  }

  demandesFiltrees(): Reservation[] {
    const f = this.filtre();
    const list = this.demandes();
    if (f === 'tous') return list;
    return list.filter((d) => d.statut === f);
  }

  accepter(reservation: Reservation): void {
    if (this.actionEnCours()) return;
    this.actionEnCours.set(reservation.id_reservation);
    this.messageErreur.set(null);

    this.reservationService.repondreReservation(reservation.id_reservation, {
      action: 'accepter'
    }).subscribe({
      next: () => {
        this.actionEnCours.set(null);
        this.messageSucces.set(`La demande pour ${reservation.annonce?.titre} a été acceptée avec succès.`);
        this.chargerDemandes(this.pagination()?.current_page || 1);
      },
      error: (err: ApiError) => {
        this.actionEnCours.set(null);
        this.messageErreur.set(err.message || 'Impossible d\'accepter cette demande.');
      }
    });
  }

  ouvrirModalRefus(reservation: Reservation): void {
    this.demandeARefuser.set(reservation);
    this.motifRefus.set('');
    this.messageErreur.set(null);
  }

  fermerModalRefus(): void {
    this.demandeARefuser.set(null);
    this.motifRefus.set('');
  }

  confirmerRefus(): void {
    const dem = this.demandeARefuser();
    const motif = this.motifRefus().trim();
    if (!dem || !motif) return;

    this.actionEnCours.set(dem.id_reservation);
    this.reservationService.repondreReservation(dem.id_reservation, {
      action: 'refuser',
      motif_refus: motif
    }).subscribe({
      next: () => {
        this.actionEnCours.set(null);
        this.fermerModalRefus();
        this.messageSucces.set('La demande a été refusée.');
        this.chargerDemandes(this.pagination()?.current_page || 1);
      },
      error: (err: ApiError) => {
        this.actionEnCours.set(null);
        this.messageErreur.set(err.message || 'Impossible de refuser cette demande.');
      }
    });
  }

  heuresRestantesMinuterie(reservation: Reservation): number | null {
    const echeance = reservation.minuterie?.date_echeance || reservation.minuterie?.date_expiration;
    if (!echeance) return null;
    const exp = new Date(echeance).getTime();
    const now = Date.now();
    const diffHours = Math.max(0, Math.floor((exp - now) / (1000 * 60 * 60)));
    return diffHours;
  }

  ouvrirModalAnnulation(reservation: Reservation): void {
    this.reservationAAnnuler.set(reservation);
  }

  fermerModalAnnulation(): void {
    this.reservationAAnnuler.set(null);
  }

  onReservationAnnulee(): void {
    this.reservationAAnnuler.set(null);
    this.messageSucces.set('La réservation a été annulée avec succès.');
    this.chargerDemandes(this.pagination()?.current_page || 1);
  }
}
