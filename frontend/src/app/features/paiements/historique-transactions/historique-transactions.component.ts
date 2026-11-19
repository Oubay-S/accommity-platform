import { Component, OnInit, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { PaiementService } from '../services/paiement.service';
import { Paiement } from '../../../shared/models/paiement.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';
import { PaginationComponent } from '../../../shared/components/pagination/pagination.component';
import { Pagination } from '../../../shared/models/api-response.model';

@Component({
  selector: 'app-historique-transactions',
  standalone: true,
  imports: [CommonModule, RouterLink, CurrencyMadPipe, PaginationComponent],
  templateUrl: './historique-transactions.component.html',
  styleUrl: './historique-transactions.component.scss',
  changeDetection: ChangeDetectionStrategy.Default
})
export class HistoriqueTransactionsComponent implements OnInit {
  private readonly paiementService = inject(PaiementService);

  readonly transactions = signal<Paiement[]>([]);
  readonly chargement = signal(true);
  readonly erreur = signal<string | null>(null);
  readonly pagination = signal<Pagination | null>(null);

  ngOnInit(): void {
    this.chargerHistorique(1);
  }

  chargerHistorique(page = 1): void {
    this.chargement.set(true);
    this.paiementService.getHistorique(page).subscribe({
      next: (res) => {
        this.transactions.set(res.items);
        this.pagination.set(res.pagination);
        this.chargement.set(false);
      },
      error: () => {
        this.erreur.set('Impossible de charger l\'historique des transactions.');
        this.chargement.set(false);
      }
    });
  }

  changerPage(page: number): void {
    this.chargerHistorique(page);
  }
}
