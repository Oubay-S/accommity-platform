# Déploiement local Accommity

> **Décision de projet :** Accommity n'a pas d'hébergement cloud. Le déploiement
> officiel de démonstration/soutenance est **local**, reproductible via Docker Compose.
> Il n'y a donc pas de CD vers un serveur distant : la CI valide, la release
> est taguée, puis le stack est lancé sur la machine de démonstration.

---

## 1. Deux modes Docker

| Mode | Fichier | Usage | URL |
| --- | --- | --- | --- |
| Développement | `docker-compose.yml` | coder, itérer, hot reload | http://localhost:4200 |
| Déploiement local | `docker-compose.deploy.yml` | démo, recette, E2E | http://localhost:8080 |

Les deux modes utilisent PostgreSQL 16 et Mailpit, mais **ne peuvent pas tourner
en même temps** (ports `5433`, `8025` et `1025` partagés).

---

## 2. Prérequis

- Docker Desktop (ou Docker Engine + Compose v2)
- 4 Go de RAM disponibles
- Ports libres : `8080`, `5433`, `8025`, `1025`
- Pour les tests E2E : Node.js 22

Vérification :

```bash
docker --version
docker compose version
node --version
```

---

## 3. Déploiement en une commande

### Windows (PowerShell)

```powershell
scripts/deploy-local.ps1
```

### Linux / macOS / Git Bash

```bash
./scripts/deploy-local.sh
```

> **Premier lancement :** le build initial peut prendre **5 à 10 minutes**
> (téléchargement des images, `composer install`, `npm ci`, build Angular).
> C'est normal : les lancements suivants utilisent le cache Docker.
> Évitez de rediriger la sortie vers `Select-Object -Last` pendant le build,
> sinon la progression n'apparaît qu'à la fin.

Le script :

1. crée `deploy/.env` depuis `deploy/.env.example` si nécessaire ;
2. génère une `APP_KEY` ;
3. construit et démarre les conteneurs ;
4. attend que l'API réponde ;
5. vérifie le frontend ;
6. affiche les URLs et les comptes de démonstration.

### Options

| Commande | Effet |
| --- | --- |
| `scripts/deploy-local.ps1 -Logs` | suit les logs après démarrage |
| `scripts/deploy-local.ps1 -Reset` | supprime conteneurs **et volumes**, puis recrée |
| `scripts/deploy-local.ps1 -NoBuild` | redémarre sans reconstruire les images |

Équivalents bash : `--logs`, `--reset`, `--no-build`.

---

## 4. Architecture du déploiement

```
Navigateur
   │ http://localhost:8080
   ▼
┌──────────────────────────────────────┐
│ web (nginx)                          │
│  - SPA Angular buildée               │
│  - /api      → backend:9000          │
│  - /storage  → volume photos         │
│  - /images   → images de démonstration│
└──────────────────┬───────────────────┘
                   │
        ┌──────────▼──────────┐
        │ backend (PHP-FPM)   │
        │ migrations + seeds  │
        └──────────┬──────────┘
                   │
        ┌──────────▼──────────┐     ┌──────────────────┐
        │ postgres            │     │ scheduler        │
        │ accommity_db        │     │ schedule:work    │
        └─────────────────────┘     └──────────────────┘
                   │
        ┌──────────▼──────────┐
        │ mailpit (SMTP/UI)   │
        └─────────────────────┘
```

Points importants :

- **Un seul origin** (`http://localhost:8080`) : pas de CORS, `apiUrl = '/api'`.
- `APP_URL=http://localhost:8080` : les URLs de photos générées par Laravel sont correctes.
- Le scheduler exécute `reservations:expire` (RG15) et `reminders:send` (RO07).
- `QUEUE_CONNECTION=sync` : pas de worker de queue nécessaire.
- Les logs Laravel partent sur `stderr` (`LOG_CHANNEL=stderr`) et sont visibles via Docker.

---

## 5. Services et ports

| Service | Conteneur | Port hôte | Rôle |
| --- | --- | --- | --- |
| `web` | `accommity-deploy-web` | `8080` | SPA + reverse proxy API |
| `backend` | `accommity-deploy-backend` | — | PHP-FPM, migrations, seeds |
| `scheduler` | `accommity-deploy-scheduler` | — | tâches planifiées |
| `postgres` | `accommity-deploy-postgres` | `5433` | base `accommity_db` |
| `mailpit` | `accommity-deploy-mailpit` | `8025` / `1025` | emails de test |

---

## 6. Comptes de démonstration

Créés par les seeders (`AdminSeeder`, `AnnoncesDemoSeeder`, `QaDemoSeeder`) :

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| Admin | `admin@accommity.ma` | `Admin1234!` |
| Hôte | `hote.demo@accommity.ma` | `Password123!` |
| Voyageur | `voyageur.demo@accommity.ma` | `Password123!` |

Le seeder QA crée aussi :

- une réservation **terminée** avec un avis voyageur (5 critères) ;
- une demande de réservation **en attente** pour l'espace hôte.

---

## 7. Commandes utiles

```bash
# État des services
docker compose -f docker-compose.deploy.yml --env-file deploy/.env ps

# Logs
docker compose -f docker-compose.deploy.yml --env-file deploy/.env logs -f
docker compose -f docker-compose.deploy.yml --env-file deploy/.env logs -f backend

# Arrêt (conserve les données)
docker compose -f docker-compose.deploy.yml --env-file deploy/.env down

# Arrêt + suppression base et photos
docker compose -f docker-compose.deploy.yml --env-file deploy/.env down -v

# Shell backend
docker compose -f docker-compose.deploy.yml --env-file deploy/.env exec backend sh

# Migrations / seed manuels
docker compose -f docker-compose.deploy.yml --env-file deploy/.env exec backend php artisan migrate --force
docker compose -f docker-compose.deploy.yml --env-file deploy/.env exec backend php artisan db:seed --force

# Tests backend dans le conteneur
docker compose -f docker-compose.deploy.yml --env-file deploy/.env exec -e DB_HOST=postgres -e DB_DATABASE=accommity_test_db backend php artisan test
```

---

## 8. Sauvegarde et restauration

Sauvegarde PostgreSQL :

```bash
docker compose -f docker-compose.deploy.yml --env-file deploy/.env exec -T postgres \
  pg_dump -U accommity_user accommity_db > backup-accommity.sql
```

Restauration :

```bash
cat backup-accommity.sql | docker compose -f docker-compose.deploy.yml --env-file deploy/.env exec -T postgres \
  psql -U accommity_user -d accommity_db
```

Les photos sont dans le volume Docker `accommity-deploy_storage-public`.

---

## 9. Mise à jour de la démonstration

```bash
git fetch origin
git checkout main
git pull --ff-only
scripts/deploy-local.ps1
```

Pour une version taguée :

```bash
git checkout v1.0.0
scripts/deploy-local.ps1
```

Les migrations sont rejouées au démarrage. Les seeders sont idempotents.

---

## 10. Dépannage

| Symptôme | Cause probable | Solution |
| --- | --- | --- |
| `502 Bad Gateway` sur `/api` | backend pas prêt / `APP_KEY` absente | `docker compose ... logs backend` |
| `413 Request Entity Too Large` | photo > 6 Mo | réduire la photo ; la limite Laravel est 5 Mo |
| Photos en `404` | volume `storage-public` absent | `deploy-local -Reset` |
| `database does not exist` | volume PostgreSQL incomplet | `deploy-local -Reset` |
| Emails absents | Mailpit non démarré | ouvrir http://localhost:8025 |
| Port déjà utilisé | stack dev encore lancé | `docker compose down` sur l'autre stack |
| Frontend affiche une page blanche | build Angular en échec | `docker compose ... logs web` puis rebuild |
| Tests backend : `postgres` introuvable | lancés hors Docker | lancer PostgreSQL ou utiliser `exec backend` |

---

## 11. Sécurité

- `deploy/.env` n'est **jamais** commité.
- `APP_DEBUG=false` par défaut en déploiement local.
- Les mots de passe de démonstration sont publics : ils ne doivent pas être
  réutilisés sur un vrai environnement.
- Aucun secret n'est embarqué dans les images grâce à `.dockerignore`.
- Les en-têtes `X-Content-Type-Options`, `X-Frame-Options` et `Referrer-Policy`
  sont ajoutés par nginx.
- Ce déploiement est prévu pour un poste local, pas pour une exposition Internet.
