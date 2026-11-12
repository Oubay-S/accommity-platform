# Architecture Technique : Accommity Platform

## 1. Vue d'ensemble (3 Tiers)
La plateforme Accommity est une application web distribuée en architecture 3 tiers :
1. **Couche Présentation (SPA Angular) :** Interface utilisateur monopage réactive développée en Angular 19, typée avec TypeScript, organisée en modules fonctionnels (Feature-driven).
2. **Couche Métier (API REST Laravel) :** Backend stateless Laravel fournissant des endpoints sécurisés par Laravel Sanctum (Bearer Token), orchestré par une architecture en couches (Controllers -> FormRequests -> Services -> Repositories -> Eloquent Models).
3. **Couche Données (PostgreSQL) :** Base de données relationnelle comprenant 24 tables avec intégrité référentielle, indexation de recherche et transactions ACID pour les réservations et paiements.

## 2. Flux de communication
- Authentification : Tokens Bearer émis par `/api/auth/login` et interceptés via `AuthInterceptor` Angular.
- Échanges de données : Données encapsulées au format JSON via `ApiResponse<T>`.
- Contrôle d'accès : Middleware Laravel (`auth:sanctum`, `role:hote`, `role:admin`) et Guards Angular (`AuthGuard`, `RoleGuard`, `HoteGuard`, `AdminGuard`).
