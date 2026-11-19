import { Component, OnInit, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ReservationService } from '../services/reservation.service';
import { Reservation } from '../../../shared/models/reservation.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';
import { PaginationComponent } from '../../../shared/components/pagination/pagination.component';
import { AnnulationModalComponent } from '../annulation-modal/annulation-modal.component';
import { Pagination } from '../../../shared/models/api-response.model';

@Component({
  selector: 'app-mes-voyages',
  standalone: true,
  imports: [CommonModule, RouterLink, CurrencyMadPipe, PaginationComponent, AnnulationModalComponent],
  templateUrl: './mes-voyages.component.html',
  styleUrl: './mes-voyages.component.scss',
  changeDetection: ChangeDetectionStrategy.Default
})
export class MesVoyagesComponent implements OnInit {
  private readonly reservationService = inject(ReservationService);
  private readonly route = inject(ActivatedRoute);

  readonly voyages = signal<Reservation[]>([]);
  readonly chargement = signal(true);
  readonly erreur = signal<string | null>(null);
  readonly filtreActif = signal<string>('tous');
  readonly pagination = signal<Pagination | null>(null);
  readonly messageSucces = signal<string | null>(null);

  readonly reservationAAnnuler = signal<Reservation | null>(null);

  ngOnInit(): void {
    if (this.route.snapshot.queryParams['demande_envoyee'] === 'true') {
      this.messageSucces.set('Votre demande de réservation a été transmise à l\'hôte avec succès ! Il dispose de 24h pour vous répondre.');
    }
    this.chargerVoyages(1);
  }

  chargerVoyages(page = 1): void {
    this.chargement.set(true);
    this.reservationService.getMesVoyages(page).subscribe({
      next: (res) => {
        this.voyages.set(res.items);
        this.pagination.set(res.pagination);
        this.chargement.set(false);
      },
      error: () => {
        this.erreur.set('Impossible de charger vos réservations.');
        this.chargement.set(false);
      }
    });
  }

  changerPage(page: number): void {
    this.chargerVoyages(page);
  }

  filtrer(statut: string): void {
    this.filtreActif.set(statut);
  }

  voyagesFiltres(): Reservation[] {
    const f = this.filtreActif();
    const list = this.voyages();
    if (f === 'tous') return list;
    return list.filter((v) => v.statut === f);
  }

  ouvrirAnnulation(reservation: Reservation): void {
    this.reservationAAnnuler.set(reservation);
  }

  fermerAnnulation(): void {
    this.reservationAAnnuler.set(null);
  }

  onReservationAnnulee(): void {
    this.reservationAAnnuler.set(null);
    this.messageSucces.set('Votre réservation a été annulée avec succès.');
    this.chargerVoyages(this.pagination()?.current_page || 1);
  }
}
