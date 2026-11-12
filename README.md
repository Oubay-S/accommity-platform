# Accommity Platform 🏡

Plateforme web moderne de gestion d'hébergements en ligne et de réservations (type Airbnb).  
Projet académique de **Gestion de Projet** (2e Année cycle d'ingénieur LSI, FST Tanger - 2026/2027).

---

## 👥 Équipe & Rôles (Groupe 6)

| Nom & Prénom | Rôle Principal | Responsabilités Clés |
| :--- | :--- | :--- |
| **NADI LAHJOUJI Mohamed Soulaimane** | Chef de projet / Architecte | Pilotage, relation MOA, validation recette |
| **OU-BRICHE Mohamed** | Back-end Lead | Modèle de données, API REST, paiements, sécurité |
| **ASRAR Taha** | Back-end Developer | Endpoints API, back-office admin, intégrité |
| **EL ARROUD Mohamed Reda** | Front-end Lead | Architecture SPA Angular, routing, state |
| **AOUICHI Omar** | Front-end / UI | Intégration écrans, composants, responsive UI |
| **EL ARABI SERGHINI Oubay** | DevOps / QA Lead | CI/CD, Docker, tests automatisés, qualité & docs |

---

## 🛠️ Stack Technique

- **Présentation :** Angular 19 SPA (TypeScript, SCSS, Standalone Components, Reactive Forms).
- **Logique Métier :** Laravel 11/12/13 API (PHP 8.2+, Sanctum, FormRequests, Services, Repositories).
- **Base de Données :** PostgreSQL 16 (24 tables relationnelles, conformité MLD).
- **Conteneurisation & DevOps :** Docker Compose, Nginx, GitHub Actions (CI/CD).

---

## 📁 Structure du Monorepo

```text
accommity-platform/
├── .github/workflows/       # Pipelines CI/CD Backend & Frontend
├── backend/                 # API REST Laravel (Architecture en couches)
│   ├── app/
│   │   ├── Console/Commands/ # Commandes planifiées (expiration 24h, rappels)
│   │   ├── Enums/            # Énumérations métier
│   │   ├── Http/Controllers/ # Contrôleurs API par domaine
│   │   ├── Http/Requests/    # Règles de validation FormRequest
│   │   ├── Http/Resources/   # Formats de sérialisation JSON
│   │   ├── Models/           # Modèles Eloquent (24 entités)
│   │   ├── Policies/         # Autorisations d'accès par rôle
│   │   ├── Services/         # Logique métier pure
│   │   └── Repositories/     # Abstraction d'accès aux données
│   ├── database/migrations/  # Schéma complet de la base
│   └── routes/api.php        # Déclarations des routes REST
├── frontend/                # SPA Angular modulaire
│   ├── src/app/
│   │   ├── core/            # Services singletons, Guards, Interceptors
│   │   ├── shared/          # Composants réutilisables, Modèles typés, Pipes
│   │   └── features/        # Modules par domaine fonctionnel (CdC 4.1 à 4.12)
│   │       ├── home/
│   │       ├── auth/
│   │       ├── profile/
│   │       ├── annonces/
│   │       ├── reservations/
│   │       ├── paiements/
│   │       ├── evaluations/
│   │       ├── notifications/
│   │       └── admin/
├── docker/                  # Dockerfiles et configuration Nginx
├── docs/                    # Documentation technique, API & RACI
└── docker-compose.yml       # Orchestration locale multi-conteneurs
```

---

## 🚀 Démarrage Rapide

### Avec Docker (Recommandé pour l'équipe) :
```bash
# Cloner le projet
git clone https://github.com/Oubay-S/accommity-platform.git
cd accommity-platform

# Lancer tous les conteneurs (Backend, Frontend, PostgreSQL, pgAdmin, Nginx)
docker-compose up -d --build
```
- Frontend Angular : `http://localhost:4200`
- API Laravel : `http://localhost:8000/api`
- pgAdmin : `http://localhost:8080` (identifiants: `admin@accommity.ma` / `admin`)

### Sans Docker (Développement local) :
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
