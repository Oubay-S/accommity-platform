# Architecture technique — Accommity Platform

## 1. Vue d'ensemble (3 tiers)

1. **Présentation — SPA Angular 22**
   - TypeScript 6, standalone components, signals, SCSS.
   - Organisation par domaines : `core`, `shared`, `features`.
   - Authentification par Bearer token, guards de rôles.

2. **Métier — API REST Laravel 12 (PHP 8.2)**
   - Architecture en couches :
     `Controllers → FormRequests → Services → Repositories → Eloquent`.
   - Réponses normalisées `ApiResponse<T>`.
   - Sanctum pour les tokens, Policies pour les autorisations.
   - Tâches planifiées : expiration des demandes (RG15) et rappels (RO07).

3. **Données — PostgreSQL 16**
   - Schéma relationnel complet (24 tables), contraintes d'intégrité,
     index de recherche et transactions ACID.

## 2. Flux d'une requête

```text
Angular
  │ HTTP + Bearer token
  ▼
Nginx
  │ /api → PHP-FPM
  ▼
Laravel
  │ Middleware auth:sanctum / role
  │ FormRequest (validation)
  │ Controller → Service → Repository
  ▼
PostgreSQL
```

- Les erreurs sont normalisées : `success=false`, `message`, `errors`, `error_code`.
- Les dates sont exposées en `YYYY-MM-DD` ou ISO 8601.
- Les identifiants sont des UUID.
- Les photos publiques sont stockées dans `backend/storage/app/public`.

## 3. Modes de déploiement

### Développement

```text
Angular dev-server (4200) ──proxy /api──► nginx (8000) ──► PHP-FPM ──► PostgreSQL (5433)
```

- Hot reload Angular.
- Mailpit pour capturer les emails.

### Déploiement local (démo / soutenance)

```text
Navigateur ──► web nginx (8080)
                 ├── SPA Angular buildée
                 ├── /api      → backend PHP-FPM
                 ├── /storage  → volume photos
                 └── /images   → images de démonstration
                                   │
                                   ├── PostgreSQL
                                   ├── scheduler (artisan schedule:work)
                                   └── Mailpit
```

- Un seul origin : pas de CORS.
- `APP_URL=http://localhost:8080`.
- Aucun hébergement cloud : voir [`DEPLOYMENT.md`](DEPLOYMENT.md).

## 4. Sécurité

- Sanctum Bearer tokens, expiration configurable (`SANCTUM_EXPIRATION`).
- Middlewares de rôles : `auth:sanctum`, `role:hote`, `role:admin`.
- Validation systématique via FormRequests.
- Mots de passe hashés (bcrypt).
- Secrets uniquement dans les fichiers `.env` locaux (ignorés par Git).
- En-têtes de sécurité nginx : `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`.
- Uploads limités à 5 Mo côté Laravel, 6 Mo côté nginx/PHP.

## 5. Qualité et CI

| Workflow | Rôle |
| --- | --- |
| Backend CI | Pint, PHPStan, migrations, PHPUnit, couverture |
| Frontend CI | type-check, Karma, build production |
| Docker Build | validation des images et compose |
| E2E Smoke | parcours Playwright sur le stack local |
| Release | artefacts tagués + checksums |

Détails : [`TESTING.md`](TESTING.md) et [`GIT_WORKFLOW.md`](GIT_WORKFLOW.md).
