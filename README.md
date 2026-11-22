# Accommity Platform 🏡

Plateforme web de gestion d'hébergements et de réservations au Maroc (type Airbnb).

Projet académique de **Gestion de Projet** — 2e année cycle d'ingénieur LSI, FST Tanger (2026/2027).

---

## 👥 Équipe & rôles (Groupe 6)

| Nom & Prénom | Rôle principal | Responsabilités clés |
| --- | --- | --- |
| **NADI LAHJOUJI Mohamed Soulaimane** | Chef de projet / Architecte | Pilotage, relation MOA, validation recette |
| **OU-BRICHE Mohamed** | Back-end Lead | Modèle de données, API REST, paiements, sécurité |
| **ASRAR Taha** | Back-end Developer | Endpoints API, back-office admin, intégrité |
| **EL ARROUD Mohamed Reda** | Front-end Lead | Architecture SPA Angular, routing, state |
| **AOUICHI Omar** | Front-end / UI | Intégration écrans, composants, responsive UI |
| **EL ARABI SERGHINI Oubay** | DevOps / QA Lead | CI/CD, Docker, tests automatisés, qualité & docs |

---

## 🛠️ Stack technique

- **Frontend :** Angular 22 (TypeScript 6, standalone components, signals, SCSS)
- **Backend :** Laravel 12 / PHP 8.2 (Sanctum, FormRequests, Services, Repositories)
- **Base de données :** PostgreSQL 16
- **Tests :** PHPUnit 11, Karma/Jasmine, Playwright
- **DevOps :** Docker Compose, Nginx, GitHub Actions

---

## 📁 Structure du monorepo

```text
accommity-platform/
├── .github/workflows/       # CI backend, frontend, Docker, E2E, release
├── backend/                 # API REST Laravel
│   ├── app/                 # Controllers, Services, Repositories, Models
│   ├── database/            # migrations, factories, seeders
│   ├── scripts/             # vérification de couverture
│   └── routes/api.php
├── frontend/                # SPA Angular
│   ├── src/app/             # core, shared, features
│   └── e2e/                 # tests Playwright
├── docker/                  # Dockerfiles, nginx, php.ini
├── deploy/                  # .env.example du déploiement local
├── docs/                    # API, architecture, tests, recette, déploiement
├── scripts/                 # déploiement local Windows / Linux
└── docker-compose.yml       # stack de développement
```

---

## 🚀 Démarrage rapide

### Mode 1 — Développement (hot reload)

```bash
docker compose up -d --build
```

| Service | URL |
| --- | --- |
| Frontend Angular | http://localhost:4200 |
| API Laravel | http://localhost:8000/api |
| Mailpit | http://localhost:8025 |
| PostgreSQL | localhost:5433 |

Le conteneur backend installe Composer au premier démarrage, génère `APP_KEY`,
applique les migrations et les seeders. Aucune commande manuelle n'est requise.

### Mode 2 — Déploiement local (démo / soutenance)

```powershell
scripts/deploy-local.ps1
```

```bash
./scripts/deploy-local.sh
```

| Service | URL |
| --- | --- |
| Application complète | http://localhost:8080 |
| API | http://localhost:8080/api |
| Mailpit | http://localhost:8025 |

> **Pas de cloud :** le projet est déployé **localement uniquement**. La CI valide,
> la release est taguée, puis le stack Docker est lancé sur la machine de démonstration.
> Voir [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

### Mode 3 — Sans Docker (développement local)

**Backend :**

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

**Frontend :**

```bash
cd frontend
npm install
npm start
```

---

## 🧪 Tests

```bash
# Backend
cd backend && php artisan test
cd backend && composer test:coverage

# Frontend
cd frontend && npm run lint
cd frontend && npm run test:ci
cd frontend && npm run build:prod

# E2E (stack local démarré sur 8080)
cd frontend && npx playwright install chromium
cd frontend && npm run test:e2e
```

Guide complet pour l'équipe : [`docs/TESTING.md`](docs/TESTING.md).

Scénarios de recette : [`docs/QA_RECETTE.md`](docs/QA_RECETTE.md).

---

## 🔁 CI/CD

| Workflow | Déclencheur | Contenu |
| --- | --- | --- |
| Backend CI | PR / push `main` (backend) | Pint, PHPStan, migrations, PHPUnit, couverture |
| Frontend CI | PR / push `main` (frontend) | audit, type-check, Karma, build prod |
| Docker Build | changements Docker | `compose config` + 4 builds |
| E2E Smoke | PR / push `main` | stack local + Playwright |
| Release | tag `v*` | artefacts + checksums + notes |

`main` est protégée : PR obligatoire, checks requis, pas de force push.

---

## 🔐 Comptes de démonstration

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| Admin | `admin@accommity.ma` | `Admin1234!` |
| Hôte | `hote.demo@accommity.ma` | `Password123!` |
| Voyageur | `voyageur.demo@accommity.ma` | `Password123!` |

Ces comptes sont réservés à la démonstration locale.

---

## 📚 Documentation

- [Contrat d'API](docs/API_CONTRACT.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Déploiement local](docs/DEPLOYMENT.md)
- [Tests](docs/TESTING.md)
- [Recette manuelle](docs/QA_RECETTE.md)
- [Workflow Git](docs/GIT_WORKFLOW.md)
- [RACI](docs/RACI_ROLES.md)
