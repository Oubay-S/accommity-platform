import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, ParamMap, Router } from '@angular/router';
import { EMPTY, catchError, switchMap, tap } from 'rxjs';
import { AnnonceCardComponent } from '../../../shared/components/annonce-card/annonce-card.component';
import { PaginationComponent } from '../../../shared/components/pagination/pagination.component';
import { SearchBarComponent } from '../../../shared/components/search-bar/search-bar.component';
import { AnnonceResume, CriteresRecherche } from '../../../shared/models/annonce.model';
import { ApiError, Pagination } from '../../../shared/models/api-response.model';
import { AnnonceService } from '../services/annonce.service';

@Component({
  selector: 'app-annonce-list',
  standalone: true,
  imports: [FormsModule, SearchBarComponent, AnnonceCardComponent, PaginationComponent],
  templateUrl: './annonce-list.component.html',
  styleUrl: './annonce-list.component.scss',
})
export class AnnonceListComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly annonceService = inject(AnnonceService);
  private readonly destroyRef = inject(DestroyRef);

  readonly criteres = signal<CriteresRecherche>({});
  readonly annonces = signal<AnnonceResume[]>([]);
  readonly pagination = signal<Pagination>({ total: 0, per_page: 12, current_page: 1, last_page: 1 });
  readonly loading = signal(true);
  readonly errorMessage = signal<string | null>(null);
  readonly filtersOpen = signal(false);

  readonly skeletons = [1, 2, 3, 4, 5, 6];

  readonly typesLogement = [
    { value: 'appartement', label: 'Appartement' },
    { value: 'maison', label: 'Maison' },
    { value: 'villa', label: 'Villa' },
    { value: 'riad', label: 'Riad' },
    { value: 'studio', label: 'Studio' },
    { value: 'chambre', label: 'Chambre' },
  ];

  readonly equipementsOptions = [
    { value: 'wifi', label: 'Wifi' },
    { value: 'climatisation', label: 'Climatisation' },
    { value: 'piscine', label: 'Piscine' },
    { value: 'parking', label: 'Parking' },
    { value: 'cuisine', label: 'Cuisine' },
    { value: 'terrasse', label: 'Terrasse' },
    { value: 'petit-déjeuner', label: 'Petit-déjeuner' },
  ];

  // Champs liés aux filtres latéraux
  prixMin: number | null = null;
  prixMax: number | null = null;
  voyageurs: number | null = null;
  tri: 'recent' | 'prix_asc' | 'prix_desc' = 'recent';

  ngOnInit(): void {
    this.route.queryParamMap
      .pipe(
        tap((params) => {
          const parsed = this.parseParams(params);
          this.criteres.set(parsed);
          this.prixMin = parsed.prix_min ?? null;
          this.prixMax = parsed.prix_max ?? null;
          this.voyageurs = parsed.voyageurs ?? null;
          this.tri = parsed.tri ?? 'recent';
          this.loading.set(true);
          this.errorMessage.set(null);
        }),
        switchMap(() =>
          this.annonceService.rechercher(this.criteres()).pipe(
            catchError((err: unknown) => {
              this.loading.set(false);
              this.annonces.set([]);
              if (err instanceof ApiError && err.status === 422) {
                const firstFieldErr = err.errors ? Object.values(err.errors)[0]?.[0] : null;
                this.errorMessage.set(firstFieldErr || err.message);
              } else {
                this.errorMessage.set('Impossible de charger les hébergements pour le moment.');
              }
              return EMPTY;
            }),
          ),
        ),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe((res) => {
        this.annonces.set(res?.items ?? []);
        this.pagination.set(
          res?.pagination ?? { total: 0, per_page: 12, current_page: 1, last_page: 1 },
        );
        this.loading.set(false);
      });
  }

  private parseParams(params: ParamMap): CriteresRecherche {
    const equipStr = params.get('equipements');
    const equipements = equipStr
      ? equipStr.split(',').map((s) => s.trim()).filter(Boolean)
      : undefined;

    const num = (key: string): number | undefined => {
      const raw = params.get(key);
      if (raw === null || raw === '') return undefined;
      const n = Number(raw);
      return Number.isFinite(n) ? n : undefined;
    };

    const triVal = params.get('tri');
    const tri = triVal === 'prix_asc' || triVal === 'prix_desc' ? triVal : 'recent';

    return {
      ville: params.get('ville') || undefined,
      id_categorie: params.get('id_categorie') || undefined,
      type_logement: params.get('type_logement') || undefined,
      voyageurs: num('voyageurs'),
      date_arrivee: params.get('date_arrivee') || undefined,
      date_depart: params.get('date_depart') || undefined,
      prix_min: num('prix_min'),
      prix_max: num('prix_max'),
      equipements,
      tri,
      page: num('page') ?? 1,
      per_page: num('per_page') ?? 12,
    };
  }

  toggleTypeLogement(type: string): void {
    const next = this.criteres().type_logement === type ? null : type;
    this.updateQuery({ type_logement: next, page: 1 });
  }

  hasEquipement(val: string): boolean {
    return (this.criteres().equipements ?? []).includes(val);
  }

  toggleEquipement(val: string): void {
    const current = new Set(this.criteres().equipements ?? []);
    if (current.has(val)) {
      current.delete(val);
    } else {
      current.add(val);
    }
    const list = Array.from(current);
    this.updateQuery({ equipements: list.length ? list.join(',') : null, page: 1 });
  }

  applySidebarNumbers(): void {
    this.updateQuery({
      prix_min: this.prixMin !== null && this.prixMin >= 0 ? this.prixMin : null,
      prix_max: this.prixMax !== null && this.prixMax >= 0 ? this.prixMax : null,
      voyageurs: this.voyageurs !== null && this.voyageurs >= 1 ? this.voyageurs : null,
      page: 1,
    });
  }

  onTriChange(): void {
    this.updateQuery({ tri: this.tri === 'recent' ? null : this.tri, page: 1 });
  }

  onPageChange(page: number): void {
    this.updateQuery({ page });
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  clearFilters(): void {
    this.filtersOpen.set(false);
    this.router.navigate([], { relativeTo: this.route, queryParams: {} });
  }

  get hasActiveFilters(): boolean {
    const c = this.criteres();
    return Boolean(
      c.ville ||
        c.id_categorie ||
        c.type_logement ||
        c.voyageurs ||
        c.date_arrivee ||
        c.date_depart ||
        c.prix_min !== undefined ||
        c.prix_max !== undefined ||
        (c.equipements && c.equipements.length > 0),
    );
  }

  private updateQuery(patch: Record<string, string | number | null | undefined>): void {
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: patch,
      queryParamsHandling: 'merge',
    });
  }
}
