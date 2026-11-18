import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AdminService } from '../services/admin.service';
import { AdminUser } from '../models/admin-user.model';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-users-management',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './users-management.component.html',
  styleUrl: './users-management.component.scss'
})
export class UsersManagementComponent implements OnInit {
  private readonly adminService = inject(AdminService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly search$ = new Subject<string>();

  readonly users = signal<AdminUser[]>([]);
  readonly loading = signal<boolean>(false);
  readonly page = signal<number>(1);
  readonly lastPage = signal<number>(1);
  readonly total = signal<number>(0);

  readonly roleFilter = signal<string>('tous');
  readonly statutFilter = signal<string>('tous');
  readonly searchQuery = signal<string>('');

  // Tiroir / Modale "Consulter"
  readonly selectedUser = signal<AdminUser | null>(null);
  readonly editForm = signal<{ nom: string; prenom: string; email: string; telephone: string }>({
    nom: '',
    prenom: '',
    email: '',
    telephone: '',
  });
  readonly saving = signal<boolean>(false);
  readonly rejectMotif = signal<string>('');

  ngOnInit(): void {
    this.search$
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((query) => {
        this.searchQuery.set(query);
        this.page.set(1);
        this.loadUsers();
      });

    this.loadUsers();
  }

  onSearch(query: string): void {
    this.search$.next(query);
  }

  setRoleFilter(role: string): void {
    this.roleFilter.set(role);
    this.page.set(1);
    this.loadUsers();
  }

  setStatutFilter(statut: string): void {
    this.statutFilter.set(statut);
    this.page.set(1);
    this.loadUsers();
  }

  setPage(p: number): void {
    if (p >= 1 && p <= this.lastPage()) {
      this.page.set(p);
      this.loadUsers();
    }
  }

  loadUsers(): void {
    this.loading.set(true);

    this.adminService.getUsers({
      role: this.roleFilter(),
      statut: this.statutFilter(),
      recherche: this.searchQuery(),
      page: this.page(),
      par_page: 15
    }).subscribe({
      next: (res) => {
        this.users.set(res.items);
        this.page.set(res.pagination.current_page);
        this.lastPage.set(res.pagination.last_page);
        this.total.set(res.pagination.total);
        this.loading.set(false);
      },
      error: (err) => {
        this.toast.error(err.message || 'Erreur lors du chargement des utilisateurs.');
        this.loading.set(false);
      }
    });
  }

  openConsulter(user: AdminUser): void {
    this.selectedUser.set(user);
    this.editForm.set({
      nom: user.nom,
      prenom: user.prenom,
      email: user.email,
      telephone: user.telephone ?? '',
    });
    this.rejectMotif.set('');
  }

  closeConsulter(): void {
    this.selectedUser.set(null);
  }

  updateEditField(field: 'nom' | 'prenom' | 'email' | 'telephone', value: string): void {
    this.editForm.update((f) => ({ ...f, [field]: value }));
  }

  saveUser(): void {
    const user = this.selectedUser();
    if (!user) return;

    this.saving.set(true);
    const form = this.editForm();

    this.adminService.updateUser(user.id_utilisateur, {
      nom: form.nom.trim(),
      prenom: form.prenom.trim(),
      email: form.email.trim(),
      telephone: form.telephone.trim() || null,
    }).subscribe({
      next: (res) => {
        this.toast.success('Informations utilisateur mises à jour.');
        this.selectedUser.set(res.user);
        this.saving.set(false);
        this.loadUsers();
      },
      error: (err) => {
        this.toast.error(err.message || 'Impossible de mettre à jour cet utilisateur.');
        this.saving.set(false);
      },
    });
  }

  deleteUser(user: AdminUser): void {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer définitivement le compte de ${user.prenom} ${user.nom} ?`)) {
      return;
    }

    this.adminService.deleteUser(user.id_utilisateur).subscribe({
      next: () => {
        this.toast.success('Utilisateur supprimé avec succès.');
        if (this.selectedUser()?.id_utilisateur === user.id_utilisateur) {
          this.closeConsulter();
        }
        this.loadUsers();
      },
      error: (err) => {
        this.toast.error(err.message || 'Impossible de supprimer cet utilisateur.');
      },
    });
  }

  validerIdentite(verificationId: string): void {
    this.adminService.valider(verificationId).subscribe({
      next: () => {
        this.toast.success("L'identité de cet hôte a été validée.");
        this.closeConsulter();
        this.loadUsers();
      },
      error: (err) => {
        this.toast.error(err.message || "Erreur lors de la validation de l'identité.");
      },
    });
  }

  rejeterIdentite(verificationId: string): void {
    const motif = this.rejectMotif().trim();
    if (!motif) {
      this.toast.error('Veuillez préciser le motif du rejet.');
      return;
    }

    this.adminService.rejeter(verificationId, motif).subscribe({
      next: () => {
        this.toast.success("L'identité a été rejetée et l'hôte notifié.");
        this.closeConsulter();
        this.loadUsers();
      },
      error: (err) => {
        this.toast.error(err.message || "Erreur lors du rejet de l'identité.");
      },
    });
  }

  toggleActif(user: AdminUser): void {
    const action = user.est_actif ? 'désactiver' : 'réactiver';
    if (!confirm(`Êtes-vous sûr de vouloir ${action} le compte de ${user.prenom} ${user.nom} ?`)) {
      return;
    }

    this.adminService.toggleUserActif(user.id_utilisateur).subscribe({
      next: (res) => {
        user.est_actif = res.user.est_actif;
        const msg = res.user.est_actif ? 'Compte réactivé avec succès.' : 'Compte suspendu avec succès.';
        this.toast.success(msg);
      },
      error: (err) => {
        this.toast.error(err.message || 'Impossible de modifier le statut de ce compte.');
      }
    });
  }
}
