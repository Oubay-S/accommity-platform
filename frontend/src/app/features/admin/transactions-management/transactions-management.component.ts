import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminService } from '../services/admin.service';
import { AdminFinanceType, AdminTransactionItem } from '../models/admin-finance.model';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-transactions-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './transactions-management.component.html',
  styleUrl: './transactions-management.component.scss'
})
export class TransactionsManagementComponent implements OnInit {
  private readonly adminService = inject(AdminService);
  private readonly toast = inject(ToastService);

  readonly activeType = signal<AdminFinanceType>('paiements');
  readonly items = signal<AdminTransactionItem[]>([]);
  readonly loading = signal<boolean>(false);
  readonly page = signal<number>(1);
  readonly lastPage = signal<number>(1);
  readonly total = signal<number>(0);

  readonly statutFilter = signal<string>('tous');
  readonly motifFilter = signal<string>('tous');

  ngOnInit(): void {
    this.loadTransactions();
  }

  setType(type: AdminFinanceType): void {
    this.activeType.set(type);
    this.statutFilter.set('tous');
    this.motifFilter.set('tous');
    this.page.set(1);
    this.loadTransactions();
  }

  onStatutChange(val: string): void {
    this.statutFilter.set(val);
    this.page.set(1);
    this.loadTransactions();
  }

  onMotifChange(val: string): void {
    this.motifFilter.set(val);
    this.page.set(1);
    this.loadTransactions();
  }

  setPage(p: number): void {
    if (p >= 1 && p <= this.lastPage()) {
      this.page.set(p);
      this.loadTransactions();
    }
  }

  loadTransactions(): void {
    this.loading.set(true);

    const type = this.activeType();
    this.adminService.getFinances({
      type,
      statut: type !== 'remboursements' ? this.statutFilter() : undefined,
      motif: type === 'remboursements' ? this.motifFilter() : undefined,
      page: this.page(),
      par_page: 15,
    }).subscribe({
      next: (res) => {
        this.items.set(res.items);
        this.page.set(res.pagination.current_page);
        this.lastPage.set(res.pagination.last_page);
        this.total.set(res.pagination.total);
        this.loading.set(false);
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors du chargement des transactions.');
        this.loading.set(false);
      },
    });
  }

  formatLabel(value?: string | null): string {
    if (!value) return 'N/A';
    return value.replace(/_/g, ' ');
  }
}
