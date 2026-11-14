# DESIGN.md: Accommity design system

Source of truth for the Angular UI. It is derived from the Google Stitch project **"Accommity Morocco Accommodation Platform"** (`projects/14491275271503202823`, 21 desktop screens, last updated 14/11/2026).

**How this file was made:**
- Read Stitch's generated design system.
- Read the HTML of 3 screens: *Accueil Rehaussé*, *Tableau de bord Administrateur*, *Mes voyages (Modale d'annulation)*.
- The other 18 screens were seen by title and thumbnail only. Check them against this file when you implement them.

> **Why this file exists:** the mockups use **two different styles**.
> - The home screen uses hand-tuned values: ink `#111827`, terracotta `#C25E30`, stone background `#FAF9F6`, thin outline SVG icons.
> - The admin and "Mes voyages" screens use Stitch's auto-generated Material 3 tokens: pure black `#000000`, darker terracotta `#9D4316`, a **lavender-tinted** background `#FBF8FF`, Material Symbols icons.
>
> Stitch's own written brief describes the first style (warm charcoal, terracotta `#C25E30`, warm off-white), so **this file standardises on the first style**. The generated M3 values are treated as artifacts. When a screen and this file disagree, this file wins; regenerate or fix the screen in Stitch.

---

## 1. Brand

Accommity books riads, villas, kasbahs and apartments in Morocco.

The visual idea is **architectural editorial + Moroccan warmth**:
- Large photography of real places.
- Quiet neutral surfaces.
- A serif voice for emotion.
- A clean sans-serif for anything functional.
- One warm accent, terracotta, reserved for actions.

| Do | Don't |
|---|---|
| Let photos carry the colour | Put terracotta on large backgrounds |
| Use hairline borders and soft tone changes for depth | Use heavy drop shadows at rest |
| Use the serif for titles and listing names | Use the serif for buttons, forms, tables, prices |
| Write all UI text in French, with prices in DH | Mix English UI labels or show `$` / `€` |

---

## 2. Colour tokens

Implement these as CSS custom properties in `frontend/src/styles/_tokens.scss`, imported by `styles.scss`. Components use the tokens only, never raw hex values.

### 2.1 Core

| Token | Value | Use |
|---|---|---|
| `--color-ink` | `#111827` | Primary text, dark buttons, active nav item, dark CTA panels |
| `--color-ink-soft` | `#374151` | Nav links, secondary headings |
| `--color-text-muted` | `#4B5563` | Captions, metadata, labels, `/ nuit` |
| `--color-text-subtle` | `#9CA3AF` | Placeholders, disabled |
| `--color-accent` | `#C25E30` | **Terracotta.** Main CTA (Rechercher, Réserver, Payer, Inscription), active states, rating stars, selected dates |
| `--color-accent-hover` | `#A84C22` | Hover / pressed state of accent; also accent **text** and links (better contrast, see §2.4) |
| `--color-accent-soft` | `#FFF4EE` (Tailwind `orange-50`) | Icon wells, soft highlights |
| `--color-accent-tint` | `#FFDBCD` | "En attente" badges, notification counters |
| `--color-cedar` | `#D4A373` | Small luxury details only (host badge icon). Optional. |

### 2.2 Surfaces and lines

| Token | Value | Use |
|---|---|---|
| `--color-bg` | `#FAF9F6` | Page background (warm stone). **Not** `#FBF8FF`. |
| `--color-surface` | `#FFFFFF` | Cards, header, modals, sidebar |
| `--color-surface-sunken` | `#F4F4F2` | Inputs inside cards, list rows in admin panels, chips at rest |
| `--color-border` | `#F3F4F6` (gray-100) | Card outlines, dividers inside cards |
| `--color-border-strong` | `#E5E7EB` (gray-200) | Input borders, search-capsule dividers, table rules |
| `--color-overlay` | `rgba(17, 24, 39, 0.6)` + `backdrop-filter: blur(4px)` | Modal backdrop |

### 2.3 Semantic and status

| Token | Background / text | Use |
|---|---|---|
| `--status-success` | `#ECFDF5` / `#065F46` | `confirmee`, `publiee`, `valide`, `reussi`, "Annulation flexible" |
| `--status-pending` | `#FFDBCD` / `#7E2C00` | `en_attente`, `en_cours` (verification), "Médiation en cours" |
| `--status-info` | `#EFF6FF` / `#1E40AF` | `en_cours` (stay in progress), info banners |
| `--status-neutral` | `#F3F4F6` / `#374151` | `terminee`, `expiree`, `desactivee`, `traite` |
| `--status-danger` | `#FFDAD6` / `#93000A` | `annulee`, `refusee`, `rejetee`, `suspendue`, `echoue`, "Urgent", destructive buttons |
| `--color-error` | `#BA1A1A` | Form errors, destructive text buttons ("Refuser") |

Status → badge mapping lives in **one** component (`shared/components/status-badge`). Its input is the API value (`en_attente`…), and it renders the French label ("En attente"). Never style statuses inline.

### 2.4 Contrast rules

- Body text `#111827` on `#FAF9F6`: ~16:1.
- Muted `#4B5563` on white: ~7.5:1.
- White on `#C25E30` = **4.3:1**. That is below WCAG AA (4.5:1) for normal text. Accent buttons must therefore use **≥ 15px semibold** labels, and **terracotta text on white must use `--color-accent-hover` `#A84C22`** (5.6:1).
- Never convey a status by colour alone; the badge always shows its label.

---

## 3. Typography

```html
<link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
```

| Token | Font | Size / line-height | Weight | Use |
|---|---|---|---|---|
| `display-hero` | Newsreader | 56/64 (mobile 36/44) | 400, `-0.02em` | Home hero only |
| `headline-lg` | Newsreader | 40/48 (mobile 28/36) | 400 | Page titles ("Tableau de bord Administrateur") |
| `headline-md` | Newsreader | 28/36 | 500 | Section titles, modal titles, listing name on detail page |
| `headline-sm` | Plus Jakarta Sans | 20/28 | 600 | Card / panel titles |
| `body-lg` | Plus Jakarta Sans | 18/28 | 400 | Hero subtitle, listing description |
| `body-md` | Plus Jakarta Sans | 15/24 | 400 | Default text |
| `body-sm` | Plus Jakarta Sans | 13/20 | 400 | Metadata, helper text |
| `label-lg` | Plus Jakarta Sans | 15/20 | 600 | Buttons |
| `label-md` | Plus Jakarta Sans | 13/18 | 600 | Nav items, table headers, chips |
| `label-sm` | Plus Jakarta Sans | 11/16 | 600–700, `0.04em`, UPPERCASE | Field captions in the search capsule ("DESTINATION"), stat-card captions |
| `price-display` | Plus Jakarta Sans | 22/28 | 700, `-0.02em` | Prices, money totals |

Rules:
- **Every number** (prices, ratings, dates, counters, references) uses `font-variant-numeric: tabular-nums`.
- Money format: `1 300 DH`, with a thin non-breaking space (` `) as thousands separator and `DH` after the number. Use the existing `currency-mad` pipe; it must output this exact format. The CdC 5.2 interface is French. `MAD` is only for legal/receipt lines.
- Listing names on cards use the sans-serif (`label-lg`/16px bold), not the serif. The serif is reserved for page and section headings.

---

## 4. Spacing, layout, radius, elevation

**Spacing** (4px micro-scale, 8px base): `--space-xs 4px`, `--space-sm 8px`, `--space-md 16px`, `--space-lg 24px`, `--space-xl 40px`, section padding `80px` (desktop) / `48px` (mobile).

**Layout**
- Public pages: content max-width `1280px` (`max-w-7xl`), side padding 16 / 24 / 32px (mobile / tablet / desktop).
- Back-office (hôte & admin): fixed sidebar `288px` + sticky top bar `80px` high; content max-width `1440px`, padding `40px`.
- Grids:
  - listing cards: 3 columns desktop, 2 tablet, 1 mobile
  - admin stat cards: 5 → 2 → 1
  - admin panels: 3 → 1
- **Responsive is mandatory from 360px** (CdC 5.2). All Stitch screens are desktop only, so mobile behaviour is defined here:
  - The search capsule stacks vertically.
  - The back-office sidebar becomes a slide-in drawer opened from a menu button.
  - Tables become stacked cards below 768px.

**Radius** (nested rule: inner radius = outer radius − padding)

| Token | Value | Use |
|---|---|---|
| `--radius-sm` | 8px | Inputs, small buttons in admin rows, thumbnails |
| `--radius-md` | 12px | Icon wells, admin cards and panels, list rows |
| `--radius-lg` | 16px | Card images, modals |
| `--radius-xl` | 24px | Listing cards (with 12px padding → 12–16px image radius), CTA panels |
| `--radius-2xl` | 32px | Hero search capsule (inner cells 20px) |
| `--radius-full` | 9999px | Public buttons, chips, badges, avatars, icon buttons |

**Elevation**

| Level | Style | Use |
|---|---|---|
| 0 | flat `--color-bg` | Page |
| 1 | `--color-surface` + `1px solid var(--color-border)`, no shadow | Cards at rest |
| 2 | `0 12px 28px -6px rgba(17,24,39,.08), 0 4px 10px -2px rgba(17,24,39,.04)` | Card hover, sticky header, admin cards (`shadow-sm` equivalent at rest is acceptable in back-office) |
| 3 | `0 24px 48px -12px rgba(17,24,39,.18)` + overlay | Modals, date picker, search capsule over hero |

**Motion**
- Default transition `200ms`, easing `cubic-bezier(0.16, 1, 0.3, 1)`.
- Card hover: lift `translateY(-2px)` + level 2 shadow; the image scales to `1.05` over `500ms`.
- Buttons scale to `0.95` when pressed.
- The hero background uses a slow Ken Burns animation (28s). Disable it, and every other non-essential animation, under `prefers-reduced-motion: reduce`.

**Icons**
- One family only: **Material Symbols Outlined**, weight 300, 20px (18px in dense rows). It is used by most back-office screens and needs no npm package.
- The home screen's inline SVG icons are close in style, so swap them during implementation.
- Every icon-only button needs an `aria-label` in French.

---

## 5. Components

Each component lives in `frontend/src/app/shared/components/` (existing folders in **bold**).

### Buttons

| Variant | Style | Example |
|---|---|---|
| Primary (accent) | bg `--color-accent`, white `label-lg`, padding `12px 24px`, `--radius-full` (public) / `--radius-md` (back-office), hover `--color-accent-hover` | Rechercher, Réserver, Payer, Inscription, Approuver |
| Dark | bg `--color-ink`, white | Explorer les destinations, Valider, Exporter, Arbitrer |
| Ghost | transparent, `1px solid --color-border-strong`, ink text, hover `--color-surface-sunken` | Annuler, Détails |
| Soft | bg `--color-surface-sunken`, ink text | Examiner, Corrections |
| Danger text | no bg, `--color-error` text, hover `--status-danger` bg | Refuser, Supprimer |
| Icon | 36–40px circle, white/90 + `backdrop-blur`, muted icon → accent on hover | Favori, Fermer |

**Rules:**
- At most **one accent button per view**.
- Loading state: disable the button, show a spinner and a label such as "Recherche en cours…".
- Disabled: 50% opacity, `cursor: not-allowed`.

### Header (**header**)
- Fixed, 80px, `white/95` + `backdrop-blur`, bottom border `--color-border`.
- Logo: an ink square icon tile + "Accommity" (800 weight) + "MOROCCO ESCAPES" caption (`label-sm`, accent).
- Nav: `label-md` ink-soft.
- Right side: "Connexion" text link + "Inscription" accent pill.
- Logged-in state: notifications bell with an accent dot, and an avatar menu.

### Search capsule (**search-bar**)
- White, `--radius-2xl`, padding 12px, level-3 shadow on the hero.
- 4 cells (Destination, Arrivée, Départ, Voyageurs), each with a `label-sm` caption and a `body-sm`/600 value.
- 1px × 36px dividers between cells.
- Accent "Rechercher" button on the right.
- Cell hover: `gray-50/80` and the caption turns accent.
- Below 768px the cells stack; on the results page the capsule becomes compact.

### Listing card (**annonce-card**)
- `--color-surface`, `--radius-xl`, padding 12px, border `--color-border`; on hover, level 2.
- Image area:
  - Image 4:3, `--radius-lg`.
  - Favourite icon button top-right.
  - Location pill bottom-left: `ink/85` + blur, white `label-md`.
- Body:
  - Name (16px bold, turns accent on hover, 1 line).
  - Spec line (`body-sm` muted, e.g. "3 suites privées • Petit-déjeuner inclus").
  - Price on the right: `price-display` 18px black + "/ nuit" in `label-sm` muted.
  - Footer above a border: accent stars + rating `4.92` + "(84 avis)".

### Rating (**rating-stars**)
- Accent stars; unfilled stars `#D1D5DB`; value in `label-md` bold.
- In input mode (the "Laisser un avis" screen): 32px stars, hover preview, keyboard arrows.

### Status badge (**status-badge**)
- Pill, padding `2px 10px`, `label-sm` (no uppercase), colours from §2.3.
- Optional leading dot (6px) for live states (e.g. "En attente" pulse on host requests).

### Inputs and forms
- Background white, border `1px --color-border-strong`, `--radius-sm`, padding `12px 16px`, `body-md`.
- Focus: border `--color-ink` + `0 0 0 3px rgba(17,24,39,.08)` ring.
- Error: border `--color-error`, plus a message below in `body-sm` error colour. The message is in French and explicit (CdC 5.2).
- Label above the field (`label-md`, ink); helper text below (`body-sm`, muted).
- Checkbox / radio: 20px, checked = accent.
- File upload (identity document, listing photos): dashed `--color-border-strong` drop zone, `--radius-lg`, icon + "Glissez vos fichiers ou parcourez", with accepted formats and max size shown.
- Multi-step form ("Créer une annonce"): a step indicator at the top; the current step is ink, completed steps are accent with a check.

### Chips / filters
- At rest: `--color-surface-sunken`, ink, `--radius-full`, padding `8px 16px`.
- Selected: ink background, white text.

### Modal (**modal**)
- Overlay `--color-overlay`.
- Panel: white, `--radius-lg`, max-width `672px`, level-3 shadow.
- Header: `headline-md` title + close icon button; bottom border.
- Body: `24px` padding, scrolls when content exceeds `75vh`.
- Footer: actions aligned right; the destructive or confirm action is last.
- Focus is trapped inside; `Esc` closes it; focus returns to the trigger.
- **Cancellation modal** (Mes voyages): it must show the refund amount **before** confirmation (CdC 4.8). Show a reservation summary row (80px thumbnail + name + price), the policy name, then a breakdown (paid / refunded / non-refundable).

### Back-office shell (hôte & admin)
- **Sidebar:** 288px, white, subtle shadow.
  - Logo + role pill ("Portail Admin", accent).
  - Nav items: `label-md`, `--radius-md`, padding `8px 16px`, icon + label + optional counter pill on the right.
  - **Active item:** ink background, white text.
  - Bottom: Paramètres, Déconnexion, user card.
- **Top bar:** search input (pill, sunken), notifications, avatar.
- **Stat card:** white, `--radius-md`, padding 24px; icon well 40px; trend pill top-right; caption `label-sm` uppercase; value `headline-md` 600 tabular; sub-line `body-sm`.
- **Action list row** (verifications, listings to approve, disputes): sunken row, `--radius-md`, padding 16px. Title `label-lg`, sub-line `body-sm`, meta (time / price) on the right, small action buttons below (`label-sm`, `--radius-sm`).
- **Tables** (users, transactions): white card; header row `label-md` muted on `--color-surface-sunken`; rows 56px with `--color-border` dividers; actions in the last column. Paginate with the **pagination** component.

### Charts (admin dashboard)
- Inline SVG or a light library.
- Series 1 = ink, series 2 = accent / `--status-pending` background.
- Dashed `--color-border` grid lines; tabular labels in `label-sm` muted.
- Always show a legend and the values.

### Feedback
- **Loading:** skeletons on `--color-surface-sunken` with a gentle pulse; spinner only inside buttons (**loading-spinner**).
- **Empty states:** icon in an accent-soft well, short `headline-sm` title, one sentence, one action (e.g. "Aucun voyage pour le moment" → "Explorer les hébergements").
- **Toasts:** bottom-right, white card with a level-2 shadow and a coloured left border (success / danger); they close after 5s.

---

## 6. Screens (Stitch → Angular)

| Stitch screen | Angular route | Feature folder |
|---|---|---|
| Accueil Rehaussé | `/` | `home` |
| Résultats de Recherche | `/annonces` | `annonces/annonce-list` |
| Détail de l'annonce (Riad Al Jazira) | `/annonces/:id` | `annonces/annonce-detail` |
| Connexion & Inscription (Fond Riad) | `/auth/login`, `/auth/register` | `auth` |
| Confirmation & Paiement | `/reservations/creer/:annonceId` → `/paiements/checkout/:id` | `reservations`, `paiements` |
| Confirmation & Reçu de réservation | `/paiements/recu/:id` | `paiements/recu-paiement` |
| Mes voyages (+ Modale d'annulation) | `/reservations/mes-voyages` | `reservations/mes-voyages` |
| Laisser un avis | (route to add) `/evaluations/creer/:reservationId` | `evaluations` |
| Mon Profil & Paramètres | `/profil`, `/profil/modifier` | `profile` |
| Vérification d'identité Hôte | `/profil/verification-identite` | `profile/verification-identite` |
| Tableau de bord Hôte | (route to add) `/hote` | new `hote/dashboard` |
| Créer une annonce (Étape 1) | `/annonces/creer` | `annonces/annonce-create` |
| Calendrier des disponibilités Hôte | `/annonces/:id/calendrier` | `annonces/calendrier-hote` |
| Demandes de réservation Hôte | `/reservations/hote/demandes` | `reservations/reservations-hote` |
| Tableau de bord Administrateur | `/admin` | `admin/dashboard` |
| Gestion des utilisateurs (Admin) | `/admin/utilisateurs` | `admin/users-management` |
| Validation des identités Hôte (Admin) | `/admin/verifications` | `admin/verifications-management` |
| Modération des annonces (Admin) | `/admin/annonces` | `admin/annonces-moderation` |
| Avis signalés & Litiges (Admin) | `/admin/avis`, `/admin/litiges` | `admin/avis-moderation`, `admin/litiges-management` |

**No mockup yet:** Notifications, Historique des paiements, Mot de passe oublié, Admin réservations & transactions. Build them from the components above.

---

## 7. Content found in the mockups to correct before showing the client

These contradict the cahier des charges or the business configuration:

| Screen | Mockup says | Correct value |
|---|---|---|
| Accueil (host CTA) | "Commission transparente de **3 %**" | **12 %** service fee (`config/hebergement.php`) |
| Admin dashboard (revenue chart) | "Hôtes 85 % / Plateforme **15 %**" | Platform 12 % |
| Accueil (trust bar) | "Assistance locale 24/7, FR & Darija", "thés offerts", "conciergerie incluse", "transferts aéroport" | Not in the CdC scope. Remove, or mark as illustrative. |
| Admin dashboard | "Séquestre", "Loi 80-14", "Exporter CSV/PDF" | Not in the CdC. Keep only if the team commits to building them. |
| Footer | "© 2025 Accommity SARL" | © 2026, project name only (academic project) |
| Home cards | "Annulation flexible sans frais" banner | Depends on each listing's policy (flexible / modérée / stricte); don't promise it globally. |

---

## 8. Implementation notes (Angular 19)

- Put tokens in `src/styles/_tokens.scss` as CSS variables, plus SCSS maps for breakpoints (`sm 640`, `md 768`, `lg 1024`, `xl 1280`). Components use the variables, never hex.
- **Tailwind is not in the project.** The Stitch HTML uses Tailwind CDN classes. Translate them to component SCSS with the tokens above; don't copy the CDN script.
- Fonts: Google Fonts `<link>` in `index.html`; `font-display: swap`.
- Stitch's generated images (riad courtyards) are fine for the demo. Listing photos come from the API (`photos_annonces` per plan-01).
- When a Stitch screen is regenerated, re-check it against this file. Stitch tends to fall back to its auto-generated M3 palette (`#9D4316`, `#FBF8FF`); reject those values.
