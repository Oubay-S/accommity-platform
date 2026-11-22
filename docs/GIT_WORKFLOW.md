# Convention Git & workflow collaboratif

## 1. Stratégie de branches

Le projet utilise un **trunk-based development** simple, aligné sur la pratique
réelle de l'équipe :

- `main` : branche stable, protégée, livrable pour la soutenance.
- `feature/<domaine>-<description>` : travail individuel.
  - Ex. `feature/pkg-evaluations`, `feature/be-reservations-workflow`.
- `fix/<description>` : correctif ciblé.
- `chore/<description>` : outillage, CI, Docker, documentation.

> La branche `develop` n'est plus utilisée : elle n'existait pas sur le dépôt
> distant et l'équipe merge directement les PR vers `main`.

## 2. Conventions de commits

Format : `<type>(<scope>): <description courte>`

| Type | Usage |
| --- | --- |
| `feat` | nouvelle fonctionnalité |
| `fix` | correction de bogue |
| `refactor` | réorganisation sans changement fonctionnel |
| `test` | ajout ou modification de tests |
| `docs` | documentation |
| `ci` | workflows GitHub Actions |
| `chore` | Docker, outillage, dépendances |

Exemples :

```text
feat(evaluations): avis bilatéraux et notes multicritères (RG27-RG28)
fix(docker): corriger la limite d'upload nginx
ci(qa): ajouter le smoke test Playwright
docs(deploy): documenter le déploiement local
```

## 3. Cycle d'une contribution

1. Se synchroniser :

   ```bash
   git switch main
   git pull --ff-only origin main
   ```

2. Créer une branche :

   ```bash
   git switch -c feature/mon-package
   ```

3. Développer et tester localement :

   ```bash
   cd backend && php artisan test
   cd frontend && npm run lint && npm run test:ci
   ```

4. Committer avec un message conventionnel.

5. Pousser et ouvrir une PR vers `main`.

6. Vérifier que les 4 checks sont verts :

   - `Backend QA & Automated Tests`
   - `Frontend QA & Automated Tests`
   - `Docker Build`
   - `E2E Smoke`

7. Obtenir au moins une revue.

8. Merger, puis supprimer la branche.

## 4. Règles de `main`

- PR obligatoire, pas de push direct.
- Checks requis et branche à jour avant merge.
- Force push et suppression interdits.
- Aucun merge si la CI est rouge.
- Aucun secret ni fichier `.env` commité.
- Une PR = un périmètre cohérent.

## 5. Release

- Tag sémantique : `v1.0.0`, `v1.0.1`, `v1.1.0`.
- Le workflow `Release` publie les artefacts et les checksums.
- Le déploiement se fait ensuite en local via `scripts/deploy-local.*`
  (voir [`DEPLOYMENT.md`](DEPLOYMENT.md)).

## 6. Bonnes pratiques

- Rebaser ou merger `main` régulièrement dans sa branche.
- Éviter les commits de formatting massifs hors périmètre.
- Ne pas modifier les packages des autres sans coordination.
- Mettre à jour la documentation quand le contrat API change.
- Ouvrir une issue pour tout bug ou dette technique identifiée.
