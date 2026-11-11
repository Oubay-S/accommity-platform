import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';
import { adminGuard } from './core/guards/admin.guard';
import { hoteGuard } from './core/guards/hote.guard';

export const routes: Routes = [
  // ── 4.1 Accueil ──
  {
    path: '',
    loadComponent: () => import('./features/home/home.component').then(m => m.HomeComponent)
  },

  // ── 4.2 Auth ──
  {
    path: 'auth/login',
    loadComponent: () => import('./features/auth/login/login.component').then(m => m.LoginComponent)
  },
  {
    path: 'auth/register',
    loadComponent: () => import('./features/auth/register/register.component').then(m => m.RegisterComponent)
  },

  // ── 4.2 & 4.3 Profil & Vérification d'identité ──
  {
    path: 'profil',
    canActivate: [authGuard],
    children: [
      {
        path: '',
        loadComponent: () => import('./features/profile/profile-view/profile-view.component').then(m => m.ProfileViewComponent)
      },
      {
        path: 'modifier',
        loadComponent: () => import('./features/profile/profile-edit/profile-edit.component').then(m => m.ProfileEditComponent)
      },
      {
        path: 'verification-identite',
        loadComponent: () => import('./features/profile/verification-identite/verification-identite.component').then(m => m.VerificationIdentiteComponent)
      }
    ]
  },

  // ── 4.4, 4.5 & 4.6 Annonces & Recherche ──
  {
    path: 'annonces',
    children: [
      {
        path: '',
        loadComponent: () => import('./features/annonces/annonce-list/annonce-list.component').then(m => m.AnnonceListComponent)
      },
      {
        path: 'creer',
        canActivate: [authGuard, hoteGuard],
        loadComponent: () => import('./features/annonces/annonce-create/annonce-create.component').then(m => m.AnnonceCreateComponent)
      },
      {
        path: ':id',
        loadComponent: () => import('./features/annonces/annonce-detail/annonce-detail.component').then(m => m.AnnonceDetailComponent)
      },
      {
        path: ':id/modifier',
        canActivate: [authGuard, hoteGuard],
        loadComponent: () => import('./features/annonces/annonce-edit/annonce-edit.component').then(m => m.AnnonceEditComponent)
      },
      {
        path: ':id/calendrier',
        canActivate: [authGuard, hoteGuard],
        loadComponent: () => import('./features/annonces/calendrier-hote/calendrier-hote.component').then(m => m.CalendrierHoteComponent)
      }
    ]
  },

  // ── 4.7 & 4.8 Réservations & Annulations ──
  {
    path: 'reservations',
    canActivate: [authGuard],
    children: [
      {
        path: 'creer/:annonceId',
        loadComponent: () => import('./features/reservations/reservation-create/reservation-create.component').then(m => m.ReservationCreateComponent)
      },
      {
        path: 'mes-voyages',
        loadComponent: () => import('./features/reservations/mes-voyages/mes-voyages.component').then(m => m.MesVoyagesComponent)
      },
      {
        path: 'hote/demandes',
        canActivate: [hoteGuard],
        loadComponent: () => import('./features/reservations/reservations-hote/reservations-hote.component').then(m => m.ReservationsHoteComponent)
      }
    ]
  },

  // ── 4.9 Paiements ──
  {
    path: 'paiements/checkout/:reservationId',
    canActivate: [authGuard],
    loadComponent: () => import('./features/paiements/checkout/checkout.component').then(m => m.CheckoutComponent)
  },
  {
    path: 'paiements/recu/:paiementId',
    canActivate: [authGuard],
    loadComponent: () => import('./features/paiements/recu-paiement/recu-paiement.component').then(m => m.RecuPaiementComponent)
  },
  {
    path: 'paiements/historique',
    canActivate: [authGuard],
    loadComponent: () => import('./features/paiements/historique-transactions/historique-transactions.component').then(m => m.HistoriqueTransactionsComponent)
  },

  // ── 4.11 Notifications ──
  {
    path: 'notifications',
    canActivate: [authGuard],
    loadComponent: () => import('./features/notifications/notification-list/notification-list.component').then(m => m.NotificationListComponent)
  },

  // ── 4.12 Administration ──
  {
    path: 'admin',
    canActivate: [authGuard, adminGuard],
    children: [
      {
        path: '',
        loadComponent: () => import('./features/admin/dashboard/dashboard.component').then(m => m.DashboardComponent)
      },
      {
        path: 'utilisateurs',
        loadComponent: () => import('./features/admin/users-management/users-management.component').then(m => m.UsersManagementComponent)
      },
      {
        path: 'verifications',
        loadComponent: () => import('./features/admin/verifications-management/verifications-management.component').then(m => m.VerificationsManagementComponent)
      },
      {
        path: 'annonces',
        loadComponent: () => import('./features/admin/annonces-moderation/annonces-moderation.component').then(m => m.AnnoncesModerationComponent)
      },
      {
        path: 'avis',
        loadComponent: () => import('./features/admin/avis-moderation/avis-moderation.component').then(m => m.AvisModerationComponent)
      },
      {
        path: 'litiges',
        loadComponent: () => import('./features/admin/litiges-management/litiges-management.component').then(m => m.LitigesManagementComponent)
      }
    ]
  },

  {
    path: '**',
    redirectTo: ''
  }
];
