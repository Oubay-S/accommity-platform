import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import { AdminDashboardStats } from '../models/admin-dashboard.model';
import { AdminUser, AdminUserPage } from '../models/admin-user.model';
import { AdminAnnonce, AdminAnnoncePage } from '../models/admin-annonce.model';
import { AdminAvis, AdminAvisPage } from '../models/admin-avis.model';
import { AdminLitige, AdminLitigePage } from '../models/admin-litige.model';
import { AdminReservationPage } from '../models/admin-reservation.model';
import { AdminFinanceType, AdminTransactionPage } from '../models/admin-finance.model';
import {
  AdminVerification,
  AdminVerificationPage,
} from '../models/admin-verification.model';

@Injectable({ providedIn: 'root' })
export class AdminService {
  private readonly api = inject(ApiService);

  /** Récupère les KPIs globaux du tableau de bord */
  getStats(): Observable<AdminDashboardStats> {
    return this.api.get<AdminDashboardStats>('admin/stats');
  }

  // ── Gestion des Utilisateurs ──
  getUsers(params: {
    role?: string;
    statut?: string;
    recherche?: string;
    page?: number;
    par_page?: number;
  }): Observable<AdminUserPage> {
    return this.api.get<AdminUserPage>('admin/users', params);
  }

  updateUser(
    id: string,
    data: { nom: string; prenom: string; email: string; telephone?: string | null },
  ): Observable<{ user: AdminUser }> {
    return this.api.put<{ user: AdminUser }>(`admin/users/${id}`, data);
  }

  deleteUser(id: string): Observable<void> {
    return this.api.delete<void>(`admin/users/${id}`);
  }

  toggleUserActif(id: string): Observable<{ user: AdminUser }> {
    return this.api.put<{ user: AdminUser }>(`admin/users/${id}/toggle-actif`);
  }

  // ── Vérifications d'identité (Hôtes) ──
  getVerifications(params: {
    statut?: string;
    recherche?: string;
    page?: number;
    par_page?: number;
  }): Observable<AdminVerificationPage> {
    return this.api.get<AdminVerificationPage>('admin/verifications', params);
  }

  valider(id: string): Observable<AdminVerification> {
    return this.api
      .put<{ verification: AdminVerification }>(`admin/verifications/${id}/valider`)
      .pipe(map((res) => res.verification ?? (res as unknown as AdminVerification)));
  }

  rejeter(id: string, motif: string): Observable<AdminVerification> {
    return this.api
      .put<{ verification: AdminVerification }>(`admin/verifications/${id}/rejeter`, { motif })
      .pipe(map((res) => res.verification ?? (res as unknown as AdminVerification)));
  }

  downloadDocument(id: string): Observable<Blob> {
    return this.api.getBlob(`admin/verifications/${id}/document`);
  }

  // ── Modération des Annonces ──
  getAnnonces(params: {
    statut?: string;
    recherche?: string;
    page?: number;
    par_page?: number;
  }): Observable<AdminAnnoncePage> {
    return this.api.get<AdminAnnoncePage>('admin/annonces', params);
  }

  approuverAnnonce(id: string): Observable<AdminAnnonce> {
    return this.api.put<AdminAnnonce>(`admin/annonces/${id}/approuver`);
  }

  suspendreAnnonce(id: string, motif: string, statut: 'suspendue' | 'rejetee' = 'suspendue'): Observable<AdminAnnonce> {
    return this.api.put<AdminAnnonce>(`admin/annonces/${id}/suspendre`, { motif, statut });
  }

  // ── Suivi des Réservations ──
  getReservations(params: {
    statut?: string;
    recherche?: string;
    page?: number;
    par_page?: number;
  }): Observable<AdminReservationPage> {
    return this.api.get<AdminReservationPage>('admin/reservations', params);
  }

  // ── Transactions Financières ──
  getFinances(params: {
    type: AdminFinanceType;
    statut?: string;
    motif?: string;
    page?: number;
    par_page?: number;
  }): Observable<AdminTransactionPage> {
    return this.api.get<AdminTransactionPage>('admin/finances', params);
  }

  // ── Modération des Avis Signalés ──
  getAvisSignales(params?: { recherche?: string; page?: number; par_page?: number }): Observable<AdminAvisPage> {
    return this.api.get<AdminAvisPage>('admin/avis/signales', params);
  }

  ignorerSignalementAvis(id: string): Observable<AdminAvis> {
    return this.api.put<AdminAvis>(`admin/avis/${id}/ignorer`);
  }

  supprimerAvis(id: string): Observable<void> {
    return this.api.delete<void>(`admin/avis/${id}`);
  }

  // ── Gestion des Litiges ──
  getLitiges(params: {
    statut?: string;
    recherche?: string;
    page?: number;
    par_page?: number;
  }): Observable<AdminLitigePage> {
    return this.api.get<AdminLitigePage>('admin/litiges', params);
  }

  resoudreLitige(id: string, decision: string): Observable<AdminLitige> {
    return this.api.put<AdminLitige>(`admin/litiges/${id}/resoudre`, { decision });
  }
}
