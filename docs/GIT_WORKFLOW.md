# Convention Git & Workflow Collaboratif

## 1. Stratégie de Branches (Git Flow allégé)
- `main` : Branche de production / soutenance. Code stable, validé par la recette.
- `develop` : Branche d'intégration active.
- `feature/<domaine>-<description>` : Branches individuelles de travail.
  - Ex: `feature/fe-annonces-cards`, `feature/be-reservations-workflow`.
- `fix/<description>` : Correctifs urgents.

## 2. Conventions de Commits (Conventional Commits)
Format : `<type>(<scope>): <description courte>`
- `feat` : Nouvelle fonctionnalité.
- `fix` : Correction de bogue.
- `refactor` : Réorganisation de code sans modification fonctionnelle.
- `test` : Ajout ou modification de tests QA.
- `docs` : Mise à jour de la documentation.
- `ci` : Modifications relatives aux workflows GitHub Actions ou Docker.
