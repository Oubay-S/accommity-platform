import { Component, computed, inject, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { EvaluationRecuesComponent } from '../../evaluations/evaluation-recues/evaluation-recues.component';

@Component({
  selector: 'app-profile-view',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, EvaluationRecuesComponent],
  templateUrl: './profile-view.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './profile-view.component.scss'
})
export class ProfileViewComponent {
  private readonly auth = inject(AuthService);

  readonly user = this.auth.currentUser;
  readonly isAdmin = this.auth.isAdmin;
  readonly isHote = this.auth.isHote;

  readonly initials = computed(() => {
    const user = this.user();
    return user ? `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase() : '?';
  });

  readonly fullName = computed(() => {
    const user = this.user();
    return user ? `${user.prenom} ${user.nom}` : '';
  });

  readonly memberSince = computed(() => {
    const iso = this.user()?.date_creation;

    return iso
      ? new Date(iso).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
      : '—';
  });

  readonly verification = computed(() => this.user()?.verification_identite ?? null);

  readonly verificationLabel = computed(() => {
    const statut = this.verification()?.statut;

    switch (statut) {
      case 'validee':
        return 'Identité vérifiée';
      case 'en_cours':
        return 'Vérification en cours';
      case 'rejetee':
        return 'Vérification rejetée';
      default:
        return 'Non vérifiée';
    }
  });

  readonly verificationClass = computed(() => {
    const statut = this.verification()?.statut;

    switch (statut) {
      case 'validee':
        return 'badge badge--success';
      case 'en_cours':
        return 'badge badge--warning';
      case 'rejetee':
        return 'badge badge--danger';
      default:
        return 'badge';
    }
  });

  constructor() {
    this.auth.refreshUser().subscribe({ error: () => undefined });
  }
}
