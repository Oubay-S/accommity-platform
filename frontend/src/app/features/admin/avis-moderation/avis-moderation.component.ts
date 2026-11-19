import { ChangeDetectionStrategy, Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AdminService } from '../services/admin.service';
import { AdminAvis } from '../models/admin-avis.model';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-avis-moderation',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './avis-moderation.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './avis-moderation.component.scss'
})
export class AvisModerationComponent implements OnInit {
  private readonly adminService = inject(AdminService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly search$ = new Subject<string>();

  readonly avis = signal<AdminAvis[]>([]);
  readonly loading = signal<boolean>(false);
  readonly page = signal<number>(1);
  readonly lastPage = signal<number>(1);
  readonly total = signal<number>(0);

  readonly searchQuery = signal<string>('');
  readonly selectedAvis = signal<AdminAvis | null>(null);

  ngOnInit(): void {
    this.search$
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((query) => {
        this.searchQuery.set(query);
        this.page.set(1);
        this.loadAvis();
      });

    this.loadAvis();
  }

  onSearch(query: string): void {
    this.search$.next(query);
  }

  loadAvis(): void {
    this.loading.set(true);

    this.adminService.getAvisSignales({
      recherche: this.searchQuery(),
      page: this.page(),
      par_page: 15,
    }).subscribe({
      next: (res) => {
        this.avis.set(res.items);
        this.page.set(res.pagination.current_page);
        this.lastPage.set(res.pagination.last_page);
        this.total.set(res.pagination.total);
        this.loading.set(false);
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors du chargement des avis signalés.');
        this.loading.set(false);
      }
    });
  }

  setPage(p: number): void {
    if (p >= 1 && p <= this.lastPage()) {
      this.page.set(p);
      this.loadAvis();
    }
  }

  openInvestigate(item: AdminAvis): void {
    this.selectedAvis.set(item);
  }

  closeInvestigate(): void {
    this.selectedAvis.set(null);
  }

  ignorer(item: AdminAvis): void {
    this.adminService.ignorerSignalementAvis(item.id_evaluation).subscribe({
      next: () => {
        this.toast.success("Signalement rejeté. L'avis est conservé et la cible notifiée.");
        this.closeInvestigate();
        this.loadAvis();
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur de traitement.');
      }
    });
  }

  supprimer(item: AdminAvis): void {
    if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement cet avis ?')) {
      return;
    }

    this.adminService.supprimerAvis(item.id_evaluation).subscribe({
      next: () => {
        this.toast.success("L'avis signalé a été supprimé et les parties notifiées.");
        this.closeInvestigate();
        this.loadAvis();
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors de la suppression.');
      }
    });
  }
}
