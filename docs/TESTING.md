# Guide des tests Accommity

> Ce document est la référence pour **tous les membres de l'équipe**.
> Il explique comment lancer les tests, comment en écrire, et ce que la CI vérifie.

---

## 1. Pourquoi ces tests ?

- Empêcher les régressions entre packages (annonces, réservations, paiements, avis, admin).
- Sécuriser `main` : aucune PR rouge ne doit être mergée.
- Documenter le comportement attendu des endpoints et des écrans.
- Donner à la soutenance une preuve de qualité mesurable (couverture + E2E).

---

## 2. Pyramide de tests

| Niveau | Outil | Emplacement | Ce qu'on vérifie |
| --- | --- | --- | --- |
| Unitaire | PHPUnit | `backend/tests/Unit` | calculs métier purs |
| Feature / API | PHPUnit + PostgreSQL | `backend/tests/Feature` | routes, validation, auth, erreurs, base |
| Composant | Karma + Jasmine | `frontend/src/**/*.spec.ts` | rendu, interactions, états |
| E2E smoke | Playwright | `frontend/e2e` | parcours réels sur le stack local |

Règle : **chaque développeur écrit les tests de son package**. Le QA maintient
la CI, les seuils, les tests E2E et la recette manuelle.

---

## 3. Lancer les tests backend

### Option A — sur l'hôte (recommandé au quotidien)

```bash
# 1. Base PostgreSQL de développement + base de test
docker compose up -d postgres

# 2. Première fois seulement
cd backend
cp .env.example .env
php artisan key:generate

# 3. Tests
php artisan test
```

La configuration `phpunit.xml` utilise :

- `DB_HOST=127.0.0.1`
- `DB_PORT=5433`
- `DB_DATABASE=accommity_test_db` (**forcé** : impossible d'écraser `accommity_db`)

Si la base de test n'existe pas (volume Docker créé avant l'ajout du script d'init) :

```bash
docker compose exec postgres psql -U accommity_user -d postgres \
  -c "CREATE DATABASE accommity_test_db;"
```

### Option B — dans le conteneur backend

```bash
docker compose exec \
  -e DB_DATABASE=accommity_test_db \
  backend php artisan test
```

### Tests ciblés

```bash
cd backend
php artisan test --filter=EvaluationTest
php artisan test tests/Feature/ReservationTest.php
```

### Couverture

```bash
cd backend
composer test:coverage
```

> La couverture locale nécessite **PCOV** ou **Xdebug**. En CI, PCOV est installé
> automatiquement. Sans driver, PHPUnit ne génère pas `clover.xml` et le seuil échoue.

Seuils bloquants :

- **statements ≥ 70 %**
- **méthodes ≥ 60 %**

Le script `backend/scripts/check-coverage.php` lit `build/coverage/clover.xml`.

---

## 4. Lancer les tests frontend

```bash
cd frontend

# Vérification TypeScript stricte (app + specs + e2e)
npm run lint

# Tests unitaires et composants, headless, avec couverture
npm run test:ci

# Mode watch pendant le développement
npm test
```

Seuils Karma dans `frontend/karma.conf.js` :

- statements 44 %
- branches 25 %
- functions 31 %
- lines 44 %

---

## 5. Lancer les tests E2E Playwright

Les E2E ont besoin du **stack de déploiement local**.

```bash
# 1. Démarrer l'application
scripts/deploy-local.ps1        # Windows
# ou ./scripts/deploy-local.sh  # Linux / macOS

# 2. Installer Chromium (première fois)
cd frontend
npx playwright install chromium

# 3. Lancer les tests
npm run test:e2e

# Interface interactive
npm run test:e2e:ui

# Rapport HTML
npx playwright show-report
```

Variables :

- `E2E_BASE_URL` (défaut : `http://localhost:8080`)
- `CI=true` : un retry, rapport HTML, pas de mode interactif.

Scénarios couverts :

1. accueil public + catalogue ;
2. connexion hôte + accès `Mes annonces` ;
3. fiche annonce + bloc d'avis ;
4. accès admin au back-office ;
5. espace `Mes évaluations` du voyageur ;
6. déconnexion.

Les tests utilisent les comptes de démonstration et l'API pour éviter les mots de
passe en dur dans les écrans. Les traces, captures et vidéos sont conservées
uniquement en cas d'échec.

---

## 6. Écrire un test backend

### Structure recommandée

```php
<?php

namespace Tests\Feature;

use App\Models\Utilisateurs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_cas_metier_est_refuse(): void
    {
        $utilisateur = User::factory()->voyageur()->create();
        Sanctum::actingAs($utilisateur);

        $this->postJson('/api/...', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'MON_CODE_METIER');
    }
}
```

### Règles

- `RefreshDatabase` sur tous les tests qui touchent la base.
- **Factories** plutôt que des insertions manuelles.
- `Sanctum::actingAs($user)` pour l'authentification.
- Vérifier l'enveloppe `ApiResponse` (`success`, `data`, `message`).
- Vérifier `error_code` pour les erreurs métier.
- Couvrir au minimum : succès, `401`, `403`, `422`, `404`/`409` si applicable.
- Pas d'appel réseau réel : `Mail::fake()`, `Notification::fake()` si besoin.
- Pas de dépendance entre tests : chaque test prépare ses données.
- Ne jamais utiliser la base `accommity_db` : `accommity_test_db` est forcée.

### Exemple de liste de cas pour un endpoint

| Cas | Attendu |
| --- | --- |
| Utilisateur valide | `200` / `201` + ressource |
| Non authentifié | `401` |
| Mauvais rôle | `403` |
| Données invalides | `422` + erreurs de champ |
| Ressource inconnue | `404` + `error_code` |
| Conflit métier | `409` + `error_code` |

---

## 7. Écrire un test frontend

```ts
import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';

describe('MonService', () => {
  let service: MonService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [MonService, provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(MonService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('appelle le bon endpoint', () => {
    service.charger().subscribe();
    const requete = http.expectOne('/api/...');
    expect(requete.request.method).toBe('GET');
    requete.flush({ success: true, data: { items: [] } });
  });
});
```

### Règles

- `HttpTestingController` : jamais de vraie requête HTTP.
- Tester le rendu, l'interaction, l'état vide et l'état d'erreur.
- Utiliser les rôles ARIA et les textes visibles pour les sélecteurs.
- Éviter de tester les détails d'implémentation (méthodes privées, structure interne).
- Ajouter un `data-testid` seulement si aucun sélecteur accessible n'est possible
  (coordination avec le front).

---

## 8. Données de test et comptes

| Usage | Compte |
| --- | --- |
| Admin | `admin@accommity.ma` / `Admin1234!` |
| Hôte | `hote.demo@accommity.ma` / `Password123!` |
| Voyageur | `voyageur.demo@accommity.ma` / `Password123!` |

Le seeder `QaDemoSeeder` crée un séjour terminé, un avis et une demande en attente.
Il ne s'exécute qu'en `APP_ENV=local`.

**Ne jamais utiliser ces comptes sur un environnement réel.**

---

## 9. Ce que la CI vérifie

| Workflow | Contenu |
| --- | --- |
| Backend CI | `composer validate`, `composer audit`, Pint ciblé, PHPStan, migrations, PHPUnit, couverture |
| Frontend CI | `npm ci`, audit prod, type-check, Karma, build prod |
| Docker Build | `compose config` + build des 4 images |
| E2E Smoke | stack local + Playwright |
| Release | packaging des artefacts sur tag `v*` |

Les checks requis sur `main` :

- `Backend QA & Automated Tests`
- `Frontend QA & Automated Tests`
- `Docker Build`
- `E2E Smoke`

---

## 10. Definition of Done d'une PR

- [ ] Tests ajoutés ou mis à jour.
- [ ] CI verte (les 4 checks).
- [ ] Couverture non en baisse.
- [ ] Aucun secret commité.
- [ ] Documentation mise à jour si le contrat API change.
- [ ] Recette manuelle effectuée pour les écrans modifiés.

---

## 11. Signaler un bug

Utiliser le template GitHub **Rapport de bug** et fournir :

1. environnement (local/CI, navigateur, commit) ;
2. sévérité ;
3. étapes de reproduction ;
4. résultat attendu ;
5. résultat obtenu (code HTTP, `error_code`, message) ;
6. logs ou capture.

Un bug sans étapes de reproduction ne peut pas être traité.

---

## 12. Erreurs fréquentes

| Erreur | Cause | Solution |
| --- | --- | --- |
| `database "accommity_test_db" does not exist` | base de test absente | créer la base ou `deploy-local -Reset` |
| `SQLSTATE[08006]` | PostgreSQL arrêté | `docker compose up -d postgres` |
| `429 Too Many Requests` sur `/auth/login` | throttle 5/min | attendre une minute, réutiliser un token, ou relancer les E2E après la fenêtre |
| `401` dans un E2E | token absent/expiré | vérifier `localStorage.accommity_auth_token` |
| `413` sur upload | photo > 6 Mo | utiliser une image plus légère |
| `postgres` introuvable | tests lancés hors Docker | utiliser `phpunit.xml` (127.0.0.1:5433) ou `exec backend` |
| E2E : page blanche | build Angular en échec | `docker compose ... logs web` |
