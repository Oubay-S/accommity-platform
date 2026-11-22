## Objectif

<!-- Décrivez le besoin fonctionnel ou technique en 2–3 phrases. -->

## Périmètre

- [ ] Backend (API / services / modèles / migrations)
- [ ] Frontend (écrans / composants / services)
- [ ] DevOps (CI / Docker / déploiement local)
- [ ] Documentation

## Changements

<!-- Liste des endpoints, écrans ou fichiers importants. -->

## Tests réalisés

- [ ] `cd backend && php artisan test` (ou tests ciblés)
- [ ] `cd frontend && npm run lint`
- [ ] `cd frontend && npm run test:ci`
- [ ] `cd frontend && npm run build:prod`
- [ ] `cd frontend && npm run test:e2e` (stack local démarré)
- [ ] `docker compose -f docker-compose.deploy.yml config -q`
- [ ] Vérification manuelle sur `http://localhost:8080`

## Checklist qualité

- [ ] La CI est verte (Backend, Frontend, Docker, E2E).
- [ ] Les nouveaux endpoints ont leurs tests (succès, 401, 403, 422, erreurs métier).
- [ ] Les nouveaux écrans ont leurs tests (rendu, interaction, état vide, erreur).
- [ ] La couverture ne baisse pas (backend ≥ 70 % statements / 60 % méthodes).
- [ ] Aucun secret ni `.env` commité.
- [ ] Pas de nouvelle entrée dans `phpstan-baseline.neon`.
- [ ] La documentation est à jour (`README`, `docs/`).
- [ ] Les migrations sont testées sur une base vide.

## Captures / logs

<!-- Ajoutez une capture ou un extrait de log si utile. -->

## Points d'attention pour la revue

<!-- Migrations, changement de contrat API, impact sur les autres packages, etc. -->
