import { Component, OnInit, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { PaiementService } from '../services/paiement.service';
import { RecuDetail } from '../../../shared/models/paiement.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';

@Component({
  selector: 'app-recu-paiement',
  standalone: true,
  imports: [CommonModule, RouterLink, CurrencyMadPipe],
  templateUrl: './recu-paiement.component.html',
  styleUrl: './recu-paiement.component.scss',
  changeDetection: ChangeDetectionStrategy.Default
})
export class RecuPaiementComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly paiementService = inject(PaiementService);

  readonly recu = signal<RecuDetail | null>(null);
  readonly chargement = signal(true);
  readonly erreur = signal<string | null>(null);
  readonly paiementReussi = signal(false);

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('paiementId');
    if (!id) {
      this.erreur.set('Identifiant de reçu manquant.');
      this.chargement.set(false);
      return;
    }

    if (this.route.snapshot.queryParams['paiement_reussi'] === 'true') {
      this.paiementReussi.set(true);
    }

    this.paiementService.getRecu(id).subscribe({
      next: (data) => {
        this.recu.set(data);
        this.chargement.set(false);
      },
      error: () => {
        this.erreur.set('Impossible de charger ce reçu ou accès non autorisé.');
        this.chargement.set(false);
      }
    });
  }

  imprimer(): void {
    window.print();
  }
}
