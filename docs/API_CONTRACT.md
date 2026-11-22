# Contrat d'API — Accommity Platform

> Source de vérité pour l'intégration Angular ↔ Laravel.
> Toute réponse de l'API utilise l'enveloppe `ApiResponse<T>`.
>
> **URLs locales :**
> - développement : `http://localhost:8000/api`
> - déploiement local : `http://localhost:8080/api`
>
> En déploiement local, la SPA et l'API partagent le même origin ; le frontend
> utilise donc `apiUrl = '/api'`.

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

## 8. Évaluations & avis (RG27, RG28)

Un avis est **bilatéral** : le voyageur évalue l'hôte et l'annonce, l'hôte évalue le voyageur.
Un seul avis par réservation et par auteur.

| Méthode | Route | Auth | Description |
| --- | --- | --- | --- |
| GET | `/api/annonces/{id}/avis?note=&page=&par_page=` | — | Avis publics d'une annonce (voyageurs uniquement) + statistiques |
| GET | `/api/evaluations/a-evaluer` | Bearer | Séjours terminés sans avis déposé par l'utilisateur |
| GET | `/api/evaluations/recues?page=&par_page=` | Bearer | Avis reçus par l'utilisateur |
| GET | `/api/evaluations/donnees?page=&par_page=` | Bearer | Avis rédigés par l'utilisateur |
| POST | `/api/evaluations` | Bearer | Dépose un avis → `201` |
| POST | `/api/evaluations/{id}/signaler` | Bearer | Signale un avis et ouvre un ticket de litige → `201` |

### Déposer un avis

```json
{
  "id_reservation": "uuid",
  "note": 5,
  "commentaire": "Un séjour excellent, je recommande vivement.",
  "notes_detaillees": {
    "proprete": 5,
    "communication": 5,
    "emplacement": 4,
    "rapport_qualite_prix": 5,
    "exactitude": 5
  }
}
```

- `notes_detaillees` est **obligatoire pour un avis voyageur** ; il est ignoré pour un avis hôte.
- `id_auteur`, `id_cible`, `id_annonce` et `type_auteur` sont dérivés côté serveur.
- La réservation doit appartenir à l'utilisateur et être au statut `terminee`.

### Ressource `Evaluation`

```json
{
  "id_evaluation": "uuid",
  "note": 4.5,
  "commentaire": "…",
  "date_creation": "2026-11-14T15:44:55+00:00",
  "auteur": {
    "id_utilisateur": "uuid",
    "prenom": "Sara",
    "nom": "M.",
    "photo_url": "url | null"
  },
  "notes_detaillees": {
    "proprete": 5,
    "communication": 5,
    "emplacement": 4,
    "rapport_qualite_prix": 5,
    "exactitude": 5
  }
}
```

`notes_detaillees` vaut `null` pour un avis hôte.

### Liste publique

```json
{
  "items": ["…Evaluation"],
  "pagination": { "total": 12, "per_page": 10, "current_page": 1, "last_page": 2 },
  "statistiques": {
    "note_moyenne": 4.6,
    "nb_avis": 12,
    "repartition": { "1": 0, "2": 1, "3": 1, "4": 4, "5": 6 }
  }
}
```

Seuls les avis `type_auteur = voyageur` sont exposés publiquement. Aucune donnée privée
(`est_signale`, `motif_signalement`, email) n'est renvoyée.

### Signalement

```json
{ "motif": "Ce commentaire contient des propos inappropriés." }
```

Création d'un ticket `ticket_litiges` (`statut = en_cours`), passage de l'avis à
`est_signale = true`. L'avis reste visible jusqu'à la décision de l'administration.

### Erreurs métier

| HTTP | `error_code` | Cas |
| --- | --- | --- |
| 404 | `RESERVATION_INTROUVABLE` | Réservation inconnue |
| 403 | `NON_PARTICIPANT` | Utilisateur étranger à la réservation |
| 422 | `RESERVATION_NON_TERMINEE` | Séjour non terminé |
| 409 | `AVIS_DEJA_DEPOSE` | Avis déjà déposé par cet auteur |
| 422 | `NOTES_DETAILLEES_REQUISES` | Avis voyageur sans notes détaillées |
| 404 | `ANNONCE_INTROUVABLE` | Annonce inconnue |
| 404 | `AVIS_INTROUVABLE` | Avis inconnu |
| 403 | `AUTO_SIGNALEMENT` | Signalement de son propre avis |
| 409 | `AVIS_DEJA_SIGNE` | Avis déjà signalé par cet utilisateur |

La moyenne du destinataire est recalculée après chaque dépôt. La note d'une annonce
est calculée dynamiquement à partir des avis voyageurs.

## 9. Angular

- `ApiService` déballe automatiquement `data` et normalise les erreurs en `ApiError` (`status`, `message`, `errors`, `errorCode`).
- En développement, `/api` est proxifié vers Laravel (`frontend/proxy.conf.mjs`, cible surchargeable par `API_PROXY_TARGET`).
