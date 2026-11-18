import { Component, DestroyRef, inject, signal, ChangeDetectionStrategy } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { ApiError } from '../../../shared/models/api-response.model';
import { TypePiece, VerificationStatut } from '../../../shared/models/user.model';
import {
  AdminVerification,
  AdminVerificationFilter,
  AdminVerificationStats,
} from '../models/admin-verification.model';
import { AdminService } from '../services/admin.service';

const EMPTY_STATS: AdminVerificationStats = { tous: 0, en_cours: 0, validee: 0, rejetee: 0 };

@Component({
  selector: 'app-verifications-management',
  standalone: true,
  templateUrl: './verifications-management.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './verifications-management.component.scss'
})
export class VerificationsManagementComponent {
  private readonly admin = inject(AdminService);
  private readonly toast = inject(ToastService);
  private readonly sanitizer = inject(DomSanitizer);
  private readonly destroyRef = inject(DestroyRef);
  private readonly search$ = new Subject<string>();

  private objectUrl: string | null = null;

  readonly items = signal<AdminVerification[]>([]);
  readonly stats = signal<AdminVerificationStats>(EMPTY_STATS);
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly loading = signal(false);
  readonly filter = signal<AdminVerificationFilter>('en_cours');
  readonly recherche = signal('');

  readonly selected = signal<AdminVerification | null>(null);
  readonly documentSrc = signal<SafeResourceUrl | null>(null);
  readonly documentMime = signal('');
  readonly documentLoading = signal(false);

  readonly motif = signal('');
  readonly motifPresets = ['Document illisible', 'Pièce expirée', 'Nom non concordant', 'Faux / Altéré'];
  readonly rejectionOpen = signal(false);
  readonly confirmingValidation = signal(false);
  readonly submitting = signal(false);

  readonly tabs: { key: AdminVerificationFilter; label: string }[] = [
    { key: 'tous', label: 'Tous' },
    { key: 'en_cours', label: 'En attente' },
    { key: 'validee', label: 'Validés' },
    { key: 'rejetee', label: 'Rejetés' },
  ];

  constructor() {
    this.search$
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed())
      .subscribe((value) => {
        this.recherche.set(value);
        this.load(1);
      });

    this.destroyRef.onDestroy(() => this.revokeDocument());
    this.load(1);
  }

  countFor(key: AdminVerificationFilter): number {
    return this.stats()[key];
  }

  selectFilter(key: AdminVerificationFilter): void {
    if (this.filter() === key) {
      return;
    }

    this.filter.set(key);
    this.load(1);
  }

  onSearch(value: string): void {
    this.search$.next(value);
  }

  goTo(page: number): void {
    if (page >= 1 && page <= this.lastPage()) {
      this.load(page);
    }
  }

  open(verification: AdminVerification): void {
    this.revokeDocument();
    this.selected.set(verification);
    this.motif.set('');
    this.rejectionOpen.set(false);
    this.confirmingValidation.set(false);

    if (verification.document_disponible) {
      this.loadDocument(verification);
    }
  }

  close(): void {
    this.revokeDocument();
    this.selected.set(null);
    this.rejectionOpen.set(false);
    this.confirmingValidation.set(false);
  }

  download(): void {
    if (!this.objectUrl) {
      return;
    }

    const extension = this.documentMime() === 'application/pdf'
      ? 'pdf'
      : this.documentMime() === 'image/png'
        ? 'png'
        : 'jpg';

    const link = document.createElement('a');
    link.href = this.objectUrl;
    link.download = `verification-${this.selected()?.id_verification ?? 'document'}.${extension}`;
    link.click();
  }

  askValidation(): void {
    this.confirmingValidation.set(true);
    this.rejectionOpen.set(false);
  }

  cancelValidation(): void {
    this.confirmingValidation.set(false);
  }

  confirmValidation(): void {
    const verification = this.selected();

    if (!verification || this.submitting()) {
      return;
    }

    this.submitting.set(true);

    this.admin.valider(verification.id_verification).subscribe({
      next: () => {
        this.submitting.set(false);
        this.toast.success('Identité validée. L\'hôte a été notifié.');
        this.close();
        this.load(1);
      },
      error: (err: ApiError) => {
        this.submitting.set(false);
        this.toast.error(err.message);
      },
    });
  }

  openRejection(): void {
    this.rejectionOpen.set(true);
    this.confirmingValidation.set(false);
  }

  usePreset(preset: string): void {
    this.motif.set(preset);
  }

  confirmRejection(): void {
    const verification = this.selected();

    if (!verification || this.submitting()) {
      return;
    }

    if (!this.motif().trim()) {
      this.toast.error('Le motif de rejet est obligatoire.');
      return;
    }

    this.submitting.set(true);

    this.admin.rejeter(verification.id_verification, this.motif().trim()).subscribe({
      next: () => {
        this.submitting.set(false);
        this.toast.success('Dossier rejeté. L\'hôte a été notifié.');
        this.close();
        this.load(1);
      },
      error: (err: ApiError) => {
        this.submitting.set(false);
        this.toast.error(err.message);
      },
    });
  }

  exportCsv(): void {
    if (this.items().length === 0) {
      return;
    }

    const rows: string[][] = [['Hôte', 'Email', 'Téléphone', 'Pièce', 'Soumission', 'Statut']];

    for (const item of this.items()) {
      rows.push([
        this.fullName(item),
        item.utilisateur?.email ?? '',
        item.utilisateur?.telephone ?? '',
        this.pieceLabel(item.type_piece),
        this.formatDate(item.date_soumission),
        this.statutLabel(item.statut),
      ]);
    }

    const csv = rows
      .map((row) => row.map((cell) => `"${cell.replace(/"/g, '""')}"`).join(';'))
      .join('\n');

    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'verifications.csv';
    link.click();
    URL.revokeObjectURL(url);
  }

  fullName(verification: AdminVerification): string {
    const user = verification.utilisateur;

    return user ? `${user.prenom} ${user.nom}` : 'Hôte inconnu';
  }

  initials(verification: AdminVerification): string {
    const user = verification.utilisateur;

    return user ? `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase() : '?';
  }

  pieceLabel(piece: TypePiece): string {
    return piece === 'cin' ? 'CIN marocaine' : 'Passeport biométrique';
  }

  statutLabel(statut: VerificationStatut): string {
    switch (statut) {
      case 'validee':
        return 'Validé';
      case 'rejetee':
        return 'Rejeté';
      default:
        return 'En attente';
    }
  }

  statutClass(statut: VerificationStatut): string {
    switch (statut) {
      case 'validee':
        return 'badge badge--success';
      case 'rejetee':
        return 'badge badge--danger';
      default:
        return 'badge badge--warning';
    }
  }

  timeAgo(iso: string | null): string {
    if (!iso) {
      return '—';
    }

    const minutes = Math.floor((Date.now() - new Date(iso).getTime()) / 60_000);

    if (minutes < 1) {
      return "à l'instant";
    }
    if (minutes < 60) {
      return `il y a ${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
      return `il y a ${hours} h`;
    }

    return `il y a ${Math.floor(hours / 24)} j`;
  }

  formatDate(iso: string | null): string {
    return iso
      ? new Date(iso).toLocaleString('fr-FR', {
          day: '2-digit',
          month: 'short',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        })
      : '—';
  }

  private load(page: number): void {
    this.loading.set(true);

    this.admin
      .getVerifications({
        statut: this.filter(),
        recherche: this.recherche() || undefined,
        page,
        par_page: 10,
      })
      .subscribe({
        next: (res) => {
          this.items.set(res.items);
          this.stats.set(res.stats);
          this.page.set(res.pagination.current_page);
          this.lastPage.set(res.pagination.last_page);
          this.total.set(res.pagination.total);
          this.loading.set(false);
        },
        error: (err: ApiError) => {
          this.loading.set(false);
          this.toast.error(err.message);
        },
      });
  }

  private loadDocument(verification: AdminVerification): void {
    this.documentLoading.set(true);

    this.admin.downloadDocument(verification.id_verification).subscribe({
      next: (blob) => {
        this.objectUrl = URL.createObjectURL(blob);
        this.documentMime.set(blob.type);
        this.documentSrc.set(this.sanitizer.bypassSecurityTrustResourceUrl(this.objectUrl));
        this.documentLoading.set(false);
      },
      error: (err: ApiError) => {
        this.documentLoading.set(false);
        this.toast.error(err.message);
      },
    });
  }

  private revokeDocument(): void {
    if (this.objectUrl) {
      URL.revokeObjectURL(this.objectUrl);
      this.objectUrl = null;
    }

    this.documentSrc.set(null);
    this.documentMime.set('');
  }
}
