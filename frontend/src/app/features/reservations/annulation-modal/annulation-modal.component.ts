import {
  Component,
  Input,
  Output,
  EventEmitter,
  OnInit,
  inject,
  signal,
  ChangeDetectionStrategy
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ReservationService } from '../services/reservation.service';
import { Reservation } from '../../../shared/models/reservation.model';
import { ApercuRemboursement } from '../../../shared/models/paiement.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';
import { ApiError } from '../../../shared/models/api-response.model';

@Component({
  selector: 'app-annulation-modal',
  standalone: true,
  imports: [CommonModule, FormsModule, CurrencyMadPipe],
  templateUrl: './annulation-modal.component.html',
  styleUrl: './annulation-modal.component.scss',
  changeDetection: ChangeDetectionStrategy.Default
})
export class AnnulationModalComponent implements OnInit {
  private readonly reservationService = inject(ReservationService);

  @Input({ required: true }) reservation!: Reservation;
  @Output() fermer = new EventEmitter<void>();
  @Output() annulee = new EventEmitter<void>();

  readonly apercu = signal<ApercuRemboursement | null>(null);
  readonly chargementApercu = signal(true);
  readonly confirmationEnCours = signal(false);
  readonly motifAnnulation = signal('');
  readonly erreur = signal<string | null>(null);

  ngOnInit(): void {
    if (!this.reservation) return;

    this.reservationService.apercuRemboursement(this.reservation.id_reservation).subscribe({
      next: (data) => {
        this.apercu.set(data);
        this.chargementApercu.set(false);
      },
      error: (err: ApiError) => {
        this.erreur.set(err.message || 'Impossible de calculer l\'aperçu du remboursement.');
        this.chargementApercu.set(false);
      }
    });
  }

  confirmerAnnulation(): void {
    const motif = this.motifAnnulation().trim();
    if (!motif) {
      this.erreur.set('Veuillez renseigner un motif pour l\'annulation.');
      return;
    }

    this.confirmationEnCours.set(true);
    this.erreur.set(null);

    this.reservationService.annulerReservation(this.reservation.id_reservation, {
      motif_annulation: motif
    }).subscribe({
      next: () => {
        this.confirmationEnCours.set(false);
        this.annulee.emit();
      },
      error: (err: ApiError) => {
        this.confirmationEnCours.set(false);
        this.erreur.set(err.message || 'Une erreur est survenue lors de l\'annulation.');
      }
    });
  }
}
