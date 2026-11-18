import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AdminService } from '../services/admin.service';
import { AdminReservation } from '../models/admin-reservation.model';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-reservations-management',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './reservations-management.component.html',
  styleUrl: './reservations-management.component.scss'
})
export class ReservationsManagementComponent implements OnInit {
  private readonly adminService = inject(AdminService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly search$ = new Subject<string>();

  readonly reservations = signal<AdminReservation[]>([]);
  readonly loading = signal<boolean>(false);
  readonly page = signal<number>(1);
  readonly lastPage = signal<number>(1);
  readonly total = signal<number>(0);

  readonly statutFilter = signal<string>('tous');
  readonly searchQuery = signal<string>('');
  readonly selectedReservation = signal<AdminReservation | null>(null);

  readonly statusTabs: { key: string; label: string }[] = [
    { key: 'tous', label: 'Toutes' },
    { key: 'en_attente', label: 'En attente' },
    { key: 'confirmee', label: 'Confirmées' },
    { key: 'en_cours', label: 'En séjour' },
    { key: 'terminee', label: 'Terminées' },
    { key: 'annulee', label: 'Annulées' },
    { key: 'refusee', label: 'Refusées' },
    { key: 'expiree', label: 'Expirées' },
  ];

  ngOnInit(): void {
    this.search$
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((query) => {
        this.searchQuery.set(query);
        this.page.set(1);
        this.loadReservations();
      });

    this.loadReservations();
  }

  onSearch(query: string): void {
    this.search$.next(query);
  }

  setStatutFilter(statut: string): void {
    this.statutFilter.set(statut);
    this.page.set(1);
    this.loadReservations();
  }

  setPage(p: number): void {
    if (p >= 1 && p <= this.lastPage()) {
      this.page.set(p);
      this.loadReservations();
    }
  }

  loadReservations(): void {
    this.loading.set(true);

    this.adminService.getReservations({
      statut: this.statutFilter(),
      recherche: this.searchQuery(),
      page: this.page(),
      par_page: 15,
    }).subscribe({
      next: (res) => {
        this.reservations.set(res.items);
        this.page.set(res.pagination.current_page);
        this.lastPage.set(res.pagination.last_page);
        this.total.set(res.pagination.total);
        this.loading.set(false);
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors du chargement des réservations.');
        this.loading.set(false);
      },
    });
  }

  openDetails(reservation: AdminReservation): void {
    this.selectedReservation.set(reservation);
  }

  closeDetails(): void {
    this.selectedReservation.set(null);
  }

  statutLabel(statut: string): string {
    const labels: Record<string, string> = {
      en_attente: 'En attente',
      confirmee: 'Confirmée',
      en_cours: 'En séjour',
      terminee: 'Terminée',
      annulee: 'Annulée',
      refusee: 'Refusée',
      expiree: 'Expirée',
    };
    return labels[statut] ?? statut;
  }

  modeLabel(mode: string): string {
    return mode === 'instantanee' ? 'Réservation instantanée' : 'Sur demande';
  }
}
