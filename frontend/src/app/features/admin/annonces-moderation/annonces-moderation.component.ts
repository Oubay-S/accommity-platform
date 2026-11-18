import { ChangeDetectionStrategy, Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AdminService } from '../services/admin.service';
import { AdminAnnonce } from '../models/admin-annonce.model';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-annonces-moderation',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './annonces-moderation.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './annonces-moderation.component.scss'
})
export class AnnoncesModerationComponent implements OnInit {
  private readonly adminService = inject(AdminService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly search$ = new Subject<string>();

  readonly annonces = signal<AdminAnnonce[]>([]);
  readonly loading = signal<boolean>(false);
  readonly page = signal<number>(1);
  readonly lastPage = signal<number>(1);
  readonly total = signal<number>(0);

  readonly statutFilter = signal<string>('en_attente');
  readonly searchQuery = signal<string>('');

  // Modale "Consulter" & Modale de suspension / rejet
  readonly detailAnnonce = signal<AdminAnnonce | null>(null);
  readonly selectedAnnonce = signal<AdminAnnonce | null>(null);
  readonly motif = signal<string>('');
  readonly actionType = signal<'suspendue' | 'rejetee'>('suspendue');
  readonly modalOpen = signal<boolean>(false);
  readonly submitting = signal<boolean>(false);

  ngOnInit(): void {
    this.search$
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((query) => {
        this.searchQuery.set(query);
        this.page.set(1);
        this.loadAnnonces();
      });

    this.loadAnnonces();
  }

  onSearch(query: string): void {
    this.search$.next(query);
  }

  setStatutFilter(statut: string): void {
    this.statutFilter.set(statut);
    this.page.set(1);
    this.loadAnnonces();
  }

  setPage(p: number): void {
    if (p >= 1 && p <= this.lastPage()) {
      this.page.set(p);
      this.loadAnnonces();
    }
  }

  loadAnnonces(): void {
    this.loading.set(true);

    this.adminService.getAnnonces({
      statut: this.statutFilter(),
      recherche: this.searchQuery(),
      page: this.page(),
      par_page: 15
    }).subscribe({
      next: (res) => {
        this.annonces.set(res.items);
        this.page.set(res.pagination.current_page);
        this.lastPage.set(res.pagination.last_page);
        this.total.set(res.pagination.total);
        this.loading.set(false);
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors du chargement des annonces.');
        this.loading.set(false);
      }
    });
  }

  firstPhoto(annonce: AdminAnnonce): string | null {
    if (!annonce.photo_url) return null;
    if (Array.isArray(annonce.photo_url)) {
      return annonce.photo_url[0] ?? null;
    }
    return annonce.photo_url;
  }

  openDetail(annonce: AdminAnnonce): void {
    this.detailAnnonce.set(annonce);
  }

  closeDetail(): void {
    this.detailAnnonce.set(null);
  }

  approuver(annonce: AdminAnnonce): void {
    if (!confirm(`Valider et publier l'annonce "${annonce.titre}" ?`)) {
      return;
    }

    this.adminService.approuverAnnonce(annonce.id_annonce).subscribe({
      next: () => {
        this.toast.success(`L'annonce "${annonce.titre}" a été publiée.`);
        this.closeDetail();
        this.loadAnnonces();
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors de l\'approbation.');
      }
    });
  }

  openSuspendModal(annonce: AdminAnnonce, type: 'suspendue' | 'rejetee' = 'suspendue'): void {
    this.selectedAnnonce.set(annonce);
    this.actionType.set(type);
    this.motif.set('');
    this.modalOpen.set(true);
  }

  closeModal(): void {
    this.modalOpen.set(false);
    this.selectedAnnonce.set(null);
  }

  confirmSuspension(): void {
    const annonce = this.selectedAnnonce();
    if (!annonce || !this.motif().trim()) {
      this.toast.error('Veuillez renseigner un motif pour justifier la modération.');
      return;
    }

    this.submitting.set(true);

    this.adminService.suspendreAnnonce(annonce.id_annonce, this.motif().trim(), this.actionType()).subscribe({
      next: () => {
        this.toast.success(`L'annonce a été mise à jour (${this.actionType()}).`);
        this.submitting.set(false);
        this.closeModal();
        this.closeDetail();
        this.loadAnnonces();
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors de la modération.');
        this.submitting.set(false);
      }
    });
  }
}
