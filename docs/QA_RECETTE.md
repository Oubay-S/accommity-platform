# Recette manuelle Accommity

> À exécuter sur le **déploiement local** (`http://localhost:8080`) avant chaque
> livraison ou démonstration. Cocher OK / KO / Bloqué et ouvrir un ticket pour
> chaque KO.

---

## 1. Préparation

```bash
scripts/deploy-local.ps1 -Reset
```

Comptes :

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| Admin | `admin@accommity.ma` | `Admin1234!` |
| Hôte | `hote.demo@accommity.ma` | `Password123!` |
| Voyageur | `voyageur.demo@accommity.ma` | `Password123!` |

Navigateurs : Chrome (principal) + Firefox ou Edge (contrôle rapide).
Tailles d'écran : 360 px, 768 px, 1440 px.

---

## 2. Matrice de recette

| # | Scénario | Prérequis | Attendu | RG | Résultat |
| --- | --- | --- | --- | --- | --- |
| 1 | Accueil public | invité | Hero, recherche, hébergements à la une, aucune erreur console | RG10 | ☐ |
| 2 | Catalogue et filtres | invité | Liste paginée, filtres ville/prix/capacité, état vide correct | RG10, RG11 | ☐ |
| 3 | Inscription | invité | Compte créé, token, redirection, validation des champs | RG01 | ☐ |
| 4 | Connexion / déconnexion | comptes démo | Guards respectés, header à jour, token supprimé | RG01, RG02 | ☐ |
| 5 | Profil | voyageur | Lecture et modification des informations, photo | RG04 | ☐ |
| 6 | Vérification identité hôte | hôte non vérifié | Upload CIN, statut `en_cours`, admin peut valider | RG03 | ☐ |
| 7 | Création annonce | hôte vérifié | Annonce + 3 photos, visible dans « Mes annonces » | RG06, RG07 | ☐ |
| 8 | Upload photo > 1 Mo | hôte | Succès, pas de `413`, photo affichée | RG07 | ☐ |
| 9 | Édition annonce | hôte | Modifications persistées après rechargement | RG06 | ☐ |
| 10 | Calendrier | hôte | Blocage/déblocage de dates persisté et visible côté voyageur | RG08, RG09 | ☐ |
| 11 | Réservation | voyageur | Calcul du total, création, statut `en_attente` ou `confirmee` | RG12 | ☐ |
| 12 | Réponse hôte | hôte | Accepter/refuser avec motif, notification au voyageur | RG13 | ☐ |
| 13 | Paiement simulé | voyageur | Paiement réussi, reçu téléchargeable, séquestre | RG20 | ☐ |
| 14 | Annulation / remboursement | voyageur | Montant remboursé conforme à la politique | RG17, RG23 | ☐ |
| 15 | Avis voyageur | séjour terminé | 5 critères enregistrés, moyenne recalculée, notification | RG27 | ☐ |
| 16 | Avis hôte | séjour terminé | Note globale, pas de critères détaillés | RG27 | ☐ |
| 17 | Doublon d'avis | avis déjà déposé | `409 AVIS_DEJA_DEPOSE` | RG27 | ☐ |
| 18 | Signalement d'avis | autre utilisateur | Ticket litige `en_cours`, avis flaggé | RG28 | ☐ |
| 19 | Admin : dashboard | admin | KPIs, réservations, finances, litiges | RO09, 4.13.1 | ☐ |
| 20 | Admin : modération | admin | Validation CIN, suspension annonce, traitement litige | RO09 | ☐ |
| 21 | Notifications in-app | utilisateur connecté | Badge, liste, marquage lu | RO06 | ☐ |
| 22 | Emails | Mailpit | Email reçu dans http://localhost:8025 | RO07 | ☐ |
| 23 | Sécurité API | invité / mauvais rôle | `401`, `403`, `422` avec `error_code` | — | ☐ |
| 24 | Responsive | 360/768/1440 | Pas de débordement, navigation utilisable | — | ☐ |

---

## 3. Détail des scénarios critiques

### 3.1 Création d'annonce + photos (RG06, RG07)

1. Se connecter en hôte.
2. Aller sur `/hote/annonces` → « Créer une annonce ».
3. Remplir titre, description, ville, type, capacité, tarif, politique.
4. Ajouter 3 photos dont une > 1 Mo.
5. Publier.

Attendu :

- redirection vers la liste ou la fiche ;
- 3 photos visibles ;
- aucune erreur `413` ;
- l'annonce apparaît dans « Mes annonces ».

### 3.2 Calendrier (RG08, RG09)

1. Ouvrir `/hote/annonces/{id}/calendrier`.
2. Bloquer une plage de 3 jours.
3. Recharger la page.
4. Ouvrir la fiche publique de l'annonce et vérifier les disponibilités.

Attendu : la plage bloquée reste bloquée après rechargement et n'est plus réservable.

### 3.3 Avis voyageur (RG27)

1. Se connecter en voyageur.
2. Aller sur `/profil/evaluations`.
3. Ouvrir le séjour terminé proposé.
4. Mettre 5 étoiles globales, renseigner les 5 critères, commentaire ≥ 10 caractères.
5. Publier.

Attendu :

- message de succès ;
- l'avis apparaît sur la fiche annonce ;
- la moyenne de l'hôte est recalculée ;
- une notification est créée pour l'hôte.

### 3.4 Doublon d'avis

1. Rejouer le dépôt sur la même réservation.

Attendu : `409` avec `AVIS_DEJA_DEPOSE`, sans doublon en base.

### 3.5 Signalement d'avis (RG28)

1. Se connecter avec un autre utilisateur.
2. Sur la fiche annonce, cliquer « Signaler cet avis ».
3. Saisir un motif ≥ 10 caractères.

Attendu :

- message de confirmation ;
- ticket `ticket_litiges` en `en_cours` ;
- avis marqué `est_signale` ;
- l'avis reste visible jusqu'à décision admin.

### 3.6 Admin (RO09)

1. Se connecter en admin → `/admin`.
2. Vérifier les 5 cartes KPI et les sections réservations/finances.
3. Modérer une annonce en attente.
4. Ouvrir un litige et le clôturer.

Attendu : chaque action persiste après rechargement et met à jour les compteurs.

### 3.7 Notifications et emails (RO06, RO07)

1. Déclencher une action (réservation, avis, validation).
2. Vérifier la cloche de notifications.
3. Ouvrir http://localhost:8025.

Attendu : notification in-app + email correspondant dans Mailpit.

---

## 4. Checklist transverse

### Sécurité

- [ ] Route protégée sans token → `401 NON_AUTHENTIFIE`.
- [ ] Mauvais rôle → `403`.
- [ ] Validation → `422` avec erreurs de champ.
- [ ] Conflit métier → `409` avec `error_code`.
- [ ] Aucun token ou mot de passe dans les logs.
- [ ] Aucun `.env` visible dans le dépôt.

### UX / accessibilité

- [ ] Navigation clavier possible (Tab, Entrée, Échap).
- [ ] Focus visible.
- [ ] Messages d'erreur en français.
- [ ] États vides explicites.
- [ ] Pas de scroll horizontal à 360 px.
- [ ] Images avec texte alternatif.

### Performance perçue

- [ ] Accueil < 3 s sur le stack local.
- [ ] Pas de spinner infini.
- [ ] Pas d'erreur console bloquante.

---

## 5. Rapport de recette

À remplir à la fin :

| Champ | Valeur |
| --- | --- |
| Date | |
| Commit / tag | |
| Testeur | |
| Navigateurs | |
| Scénarios OK | / 24 |
| Scénarios KO | |
| Bloquants restants | |
| Décision | GO / NO-GO |

Joindre les captures des KO et les liens vers les issues GitHub.
