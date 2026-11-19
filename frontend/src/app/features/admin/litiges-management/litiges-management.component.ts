import { ChangeDetectionStrategy, Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AdminService } from '../services/admin.service';
import { AdminLitige } from '../models/admin-litige.model';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-litiges-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './litiges-management.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './litiges-management.component.scss'
})
export class LitigesManagementComponent implements OnInit {
  private readonly adminService = inject(AdminService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly search$ = new Subject<string>();

  readonly litiges = signal<AdminLitige[]>([]);
  readonly loading = signal<boolean>(false);
  readonly page = signal<number>(1);
  readonly lastPage = signal<number>(1);
  readonly total = signal<number>(0);

  readonly statutFilter = signal<string>('en_cours');
  readonly searchQuery = signal<string>('');

  // Modale "Gérer" (détails + arbitrage / clôture)
  readonly selectedLitige = signal<AdminLitige | null>(null);
  readonly decision = signal<string>('');
  readonly modalOpen = signal<boolean>(false);
  readonly submitting = signal<boolean>(false);

  ngOnInit(): void {
    this.search$
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((query) => {
        this.searchQuery.set(query);
        this.page.set(1);
        this.loadLitiges();
      });

    this.loadLitiges();
  }

  onSearch(query: string): void {
    this.search$.next(query);
  }

  setStatutFilter(statut: string): void {
    this.statutFilter.set(statut);
    this.page.set(1);
    this.loadLitiges();
  }

  setPage(p: number): void {
    if (p >= 1 && p <= this.lastPage()) {
      this.page.set(p);
      this.loadLitiges();
    }
  }

  loadLitiges(): void {
    this.loading.set(true);

    this.adminService.getLitiges({
      statut: this.statutFilter(),
      recherche: this.searchQuery(),
      page: this.page(),
      par_page: 15
    }).subscribe({
      next: (res) => {
        this.litiges.set(res.items);
        this.page.set(res.pagination.current_page);
        this.lastPage.set(res.pagination.last_page);
        this.total.set(res.pagination.total);
        this.loading.set(false);
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors du chargement des litiges.');
        this.loading.set(false);
      }
    });
  }

  openManageModal(litige: AdminLitige): void {
    this.selectedLitige.set(litige);
    this.decision.set('');
    this.modalOpen.set(true);
  }

  closeModal(): void {
    this.modalOpen.set(false);
    this.selectedLitige.set(null);
  }

  confirmResolution(): void {
    const litige = this.selectedLitige();
    if (!litige || !this.decision().trim()) {
      this.toast.error('Veuillez formuler une décision pour clôturer le litige.');
      return;
    }

    this.submitting.set(true);

    this.adminService.resoudreLitige(litige.id_ticket, this.decision().trim()).subscribe({
      next: () => {
        this.toast.success('Le litige a été résolu et les parties concernées ont été notifiées.');
        this.submitting.set(false);
        this.closeModal();
        this.loadLitiges();
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors de la résolution.');
        this.submitting.set(false);
      }
    });
  }
}
