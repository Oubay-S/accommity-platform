# Contrat d'API — Accommity Platform

> Source de vérité pour l'intégration Angular ↔ Laravel.
> Toute réponse de l'API utilise l'enveloppe `ApiResponse<T>`.

## 1. Enveloppe

### Succès — `200 OK` / `201 Created`

```json
{
  "success": true,
  "message": "Profil mis à jour.",
  "data": { }
}
```

`data` est omis quand il n'y a rien à renvoyer (ex. logout).

### Erreur

```json
{
  "success": false,
  "message": "Les données fournies sont invalides.",
  "errors": { "email": ["L'adresse e-mail est déjà utilisée."] },
  "error_code": "VALIDATION_ECHOUEE"
}
```

| HTTP | `error_code` | Cas |
| --- | --- | --- |
| 401 | `NON_AUTHENTIFIE` | Token absent/expiré |
| 401 | `IDENTIFIANTS_INVALIDES` | Email/mot de passe incorrect |
| 403 | `COMPTE_INACTIF` | Compte désactivé (RG05) |
| 403 | `ACCES_REFUSE` | Policy / rôle insuffisant |
| 403 | `HOTE_REQUIS` | Action réservée aux hôtes (RG03) |
| 403 | `ADMIN_REQUIS` | Action réservée à l'administration |
| 404 | `INTROUVABLE` | Ressource inexistante |
| 404 | `DOCUMENT_INTROUVABLE` | Fichier d'identité absent |
| 409 | `VERIFICATION_EN_COURS` | Dossier déjà en cours |
| 409 | `DEJA_VERIFIE` | Identité déjà validée |
| 422 | `VALIDATION_ECHOUEE` | Erreur de formulaire |
| 422 | `MOTIF_REQUIS` | Rejet sans motif |
| 422 | `MOT_DE_PASSE_ACTUEL_INVALIDE` | Changement de mot de passe |
| 429 | `TROP_DE_TENTATIVES` | Rate limiting |

## 2. Conventions

- **Authentification** : `Authorization: Bearer <token>` (Sanctum). Token valable 24 h (`SANCTUM_EXPIRATION`, en minutes).
- **Identifiants** : UUID (`id_utilisateur`, `id_annonce`, …), jamais d'entier auto-incrémenté.
- **Dates de calendrier** : `YYYY-MM-DD`. **Horodatages** : ISO 8601 (`2026-11-14T15:44:55+00:00`).
- **Rate limiting** : login 5/min, register 10/min.

## 3. Authentification

| Méthode | Route | Auth | Description |
| --- | --- | --- | --- |
| POST | `/api/auth/register` | — | Inscription + connexion automatique |
| POST | `/api/auth/login` | — | Connexion |
| POST | `/api/auth/logout` | Bearer | Révoque le token courant |
| GET | `/api/auth/me` | Bearer | Utilisateur + profil + vérification |

**Register** (`nom`, `prenom`, `email`, `password`, `password_confirmation`, `telephone`*, `role` = `voyageur|hote`)
`*` requis si `role = hote` (RG03).

**Réponse `201`** :

```json
{
  "success": true,
  "message": "Compte créé avec succès.",
  "data": {
    "user": { "...": "UserResource" },
    "token": "1|...",
    "token_type": "Bearer"
  }
}
```

`UserResource` :

```json
{
  "id_utilisateur": "uuid",
  "nom": "El Alami",
  "prenom": "Amina",
  "email": "amina@example.com",
  "telephone": "0612345678",
  "role": "voyageur | hote | admin",
  "est_hote": false,
  "est_voyageur": true,
  "est_admin": false,
  "est_actif": true,
  "est_hote_verifie": false,
  "date_creation": "2026-11-14T15:44:55+00:00",
  "profil": { "id_profil": "uuid", "photo_url": "http://…/storage/profils/x.png", "description": "…", "note_moyenne": 4.92 },
  "verification_identite": { "id_verification": "uuid", "type_piece": "cin", "statut": "en_cours", "date_soumission": "…", "date_traitement": null, "motif_rejet": null }
}
```

## 4. Profil (RG04)

| Méthode | Route | Corps | Réponse |
| --- | --- | --- | --- |
| GET | `/api/profil` | — | `{ profil }` (créé si absent) |
| PUT | `/api/profil` | `nom`, `prenom`, `email`, `telephone`, `description` (tous optionnels) | `{ user, profil }` |
| PUT | `/api/profil/password` | `current_password`, `password`, `password_confirmation` | message |
| POST | `/api/profil/photo` | multipart `photo` (JPG/PNG/WEBP ≤ 2 Mo) | `{ profil }` |

Le changement de mot de passe révoque les autres sessions (le token courant est conservé).

## 5. Vérification d'identité (RG03)

| Méthode | Route | Auth | Corps / Réponse |
| --- | --- | --- | --- |
| POST | `/api/profil/verification` | Hôte | multipart `type_piece` (`cin|passeport`) + `document` (JPG/PNG/PDF ≤ 5 Mo) → `201 { verification }` |
| GET | `/api/profil/verification/statut` | Bearer | `{ verification: … | null }` |

Cycle : `en_cours` → `validee` | `rejetee`. Une seule demande ouverte ; resoumission possible après rejet ; blocage si déjà validée.

### Back-office (rôle admin)

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/api/admin/verifications?statut=en_cours` | Liste paginée (`items`, `pagination`) |
| PUT | `/api/admin/verifications/{id}/valider` | Valide + notifie l'hôte |
| PUT | `/api/admin/verifications/{id}/rejeter` | `{ motif }` obligatoire + notifie |
| GET | `/api/admin/verifications/{id}/document` | Téléchargement privé (streamed) |

Le document n'est **jamais** accessible publiquement (loi 09-08).

## 6. Notifications (RO06, RO07)

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/api/notifications?non_lues=1&par_page=15` | `{ items, pagination, non_lues }` |
| GET | `/api/notifications/non-lues` | `{ non_lues }` (badge) |
| PUT | `/api/notifications/{id}/lue` | Marque une notification possédée |
| PUT | `/api/notifications/tout-lu` | `{ marquees }` |

```json
{
  "id_notification": "uuid",
  "titre": "Réservation confirmée",
  "contenu": "…",
  "type_alerte": "reservation | paiement | avis | rappel | systeme",
  "est_lue": false,
  "date_creation": "…",
  "id_reservation": "uuid | null",
  "data": { "reservation_id": "uuid" }
}
```

Rappels planifiés : `php artisan reminders:send` (hôte 48 h, voyageur 24 h), idempotent.

## 7. Annonces hôte & calendrier (RG06, RG07, RG08, RG09)

Routes protégées par `auth:sanctum` + `role.hote` (hôte vérifié RG03).

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/api/annonces/references` | Référentiels publics : villes, politiques, types de logement |
| GET | `/api/hote/annonces?statut=&recherche=&page=&per_page=` | Mes annonces + `statistiques` par statut |
| GET | `/api/hote/annonces/{id}` | Détail d'une annonce même non publiée |
| POST | `/api/hote/annonces` | Création `multipart/form-data` avec `photos[]` → `201` |
| PUT | `/api/hote/annonces/{id}` | Mise à jour des champs (JSON, sans photos) |
| DELETE | `/api/hote/annonces/{id}` | Suppression — `409` si réservations actives |
| POST | `/api/hote/annonces/{id}/photos` | Ajout de photos `photos[]` (max 10 au total) |
| DELETE | `/api/hote/annonces/{id}/photos?url=` | Suppression d'une photo |
| GET | `/api/hote/annonces/{id}/calendrier?debut=&fin=` | Blocages manuels + nuits réservées |
| POST | `/api/hote/annonces/{id}/bloquer` | `{ date_debut, date_fin }` inclusif |
| POST | `/api/hote/annonces/{id}/debloquer` | `{ date_debut, date_fin }` inclusif |

Codes d'erreur métier : `ANNONCE_INTROUVABLE` (404), `ACCES_REFUSE` (403),
`ANNONCE_AYANT_RESERVATIONS` (409), `DATES_RESERVEES` (409), `VALIDATION_ECHOUEE` (422).

Photos : stockées dans `storage/app/public/annonces/{id_annonce}/`, servies via
`/storage/...` (nécessite `php artisan storage:link`), URLs absolues dans les réponses.

## 8. Angular

- `ApiService` déballe automatiquement `data` et normalise les erreurs en `ApiError` (`status`, `message`, `errors`, `errorCode`).
- En développement, `/api` est proxifié vers Laravel (`frontend/proxy.conf.mjs`, cible surchargeable par `API_PROXY_TARGET`).
