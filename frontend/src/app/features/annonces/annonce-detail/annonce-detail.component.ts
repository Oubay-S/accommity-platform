import {
  Component,
  DestroyRef,
  ElementRef,
  HostListener,
  OnInit,
  ViewChild,
  computed,
  inject,
  signal,
  ChangeDetectionStrategy
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { BehaviorSubject, EMPTY, catchError, combineLatest, forkJoin, map, switchMap, tap } from 'rxjs';
import { RatingStarsComponent } from '../../../shared/components/rating-stars/rating-stars.component';
import { AnnonceDetail } from '../../../shared/models/annonce.model';
import { ApiError } from '../../../shared/models/api-response.model';
import { CurrencyMadPipe } from '../../../shared/pipes/currency-mad.pipe';
import { EvaluationListComponent } from '../../evaluations/evaluation-list/evaluation-list.component';
import { AnnonceService } from '../services/annonce.service';

interface CalendarDay {
  iso: string;
  day: number | null;
  disabled: boolean;
  unavailable: boolean;
}

interface CalendarMonth {
  label: string;
  days: CalendarDay[];
}

@Component({
  selector: 'app-annonce-detail',
  standalone: true,
  imports: [RouterLink, CurrencyMadPipe, RatingStarsComponent, EvaluationListComponent],
  templateUrl: './annonce-detail.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './annonce-detail.component.scss',
})
export class AnnonceDetailComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly annonceService = inject(AnnonceService);
  private readonly destroyRef = inject(DestroyRef);

  @ViewChild('lightboxCloseBtn') lightboxCloseBtn?: ElementRef<HTMLButtonElement>;
  @ViewChild('lightboxPanel') lightboxPanel?: ElementRef<HTMLElement>;

  readonly annonce = signal<AnnonceDetail | null>(null);
  readonly unavailableDates = signal<Set<string>>(new Set());
  readonly loading = signal(true);
  readonly notFound = signal(false);
  /** Erreur réseau / serveur : distincte d'une annonce introuvable (404). */
  readonly loadError = signal(false);
  private readonly reload$ = new BehaviorSubject<void>(undefined);

  // Paramètres transmis depuis la recherche vers /reservations/creer/:id
  readonly forwardQueryParams = signal<Record<string, string>>({});

  // Lightbox
  readonly lightboxOpen = signal(false);
  readonly lightboxIndex = signal(0);
  private previousFocus: HTMLElement | null = null;

  // Calendrier (mois affiché à gauche = baseMonth)
  readonly baseMonth = signal<Date>(this.startOfMonth(new Date()));
  readonly weekdayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

  readonly calendarMonths = computed<CalendarMonth[]>(() => {
    const start = this.baseMonth();
    const second = new Date(start.getFullYear(), start.getMonth() + 1, 1);
    return [this.buildMonth(start), this.buildMonth(second)];
  });

  readonly canGoPrevMonth = computed(() => {
    const current = this.startOfMonth(new Date());
    return this.baseMonth().getTime() > current.getTime();
  });

  private readonly equipementIcons: Record<string, string> = {
    wifi: 'wifi',
    climatisation: 'ac_unit',
    piscine: 'pool',
    parking: 'local_parking',
    cuisine: 'kitchen',
    terrasse: 'deck',
    'petit-déjeuner': 'free_breakfast',
    'petit dejeuner': 'free_breakfast',
    television: 'tv',
    tv: 'tv',
    lave_linge: 'local_laundry_service',
    'lave-linge': 'local_laundry_service',
    chauffage: 'thermostat',
  };

  ngOnInit(): void {
    this.route.queryParamMap
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((qp) => {
        const forwarded: Record<string, string> = {};
        for (const key of ['date_arrivee', 'date_depart', 'voyageurs']) {
          const val = qp.get(key);
          if (val) forwarded[key] = val;
        }
        this.forwardQueryParams.set(forwarded);
      });

    combineLatest([this.route.paramMap, this.reload$])
      .pipe(
        map(([params]) => params.get('id') ?? ''),
        tap(() => {
          this.loading.set(true);
          this.notFound.set(false);
          this.loadError.set(false);
          this.annonce.set(null);
        }),
        switchMap((id) => {
          return forkJoin({
            annonce: this.annonceService.getAnnonce(id),
            dispo: this.annonceService.getDisponibilites(id),
          }).pipe(
            catchError((err: unknown) => {
              this.loading.set(false);
              if (err instanceof ApiError && err.status === 404) {
                this.notFound.set(true);
              } else {
                this.loadError.set(true);
              }
              return EMPTY;
            }),
          );
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(({ annonce, dispo }) => {
        this.annonce.set(annonce);
        this.unavailableDates.set(new Set(dispo?.dates_indisponibles ?? []));
        this.loading.set(false);
      });
  }

  recharger(): void {
    this.reload$.next();
  }

  getEquipementIcon(eq: string): string {
    return this.equipementIcons[eq.trim().toLowerCase()] ?? 'check_circle';
  }

  getTypeLabel(type: string): string {
    if (!type) return 'Hébergement';
    return type.charAt(0).toUpperCase() + type.slice(1);
  }

  getPolitiqueLabel(type: string): string {
    switch (type) {
      case 'flexible':
        return 'Annulation flexible';
      case 'moderee':
        return 'Annulation modérée';
      case 'stricte':
        return 'Annulation stricte';
      default:
        return type;
    }
  }

  getHoteYear(dateIso: string | null | undefined): string {
    if (!dateIso) return String(new Date().getFullYear());
    return String(new Date(dateIso).getFullYear());
  }

  getHoteInitial(prenom: string | undefined): string {
    return (prenom || 'H').charAt(0).toUpperCase();
  }

  prevMonth(): void {
    if (!this.canGoPrevMonth()) return;
    const d = this.baseMonth();
    this.baseMonth.set(new Date(d.getFullYear(), d.getMonth() - 1, 1));
  }

  nextMonth(): void {
    const d = this.baseMonth();
    this.baseMonth.set(new Date(d.getFullYear(), d.getMonth() + 1, 1));
  }

  openLightbox(index: number): void {
    const photos = this.annonce()?.photos ?? [];
    if (!photos.length) return;
    this.previousFocus = typeof document !== 'undefined' ? (document.activeElement as HTMLElement) : null;
    this.lightboxIndex.set(Math.max(0, Math.min(index, photos.length - 1)));
    this.lightboxOpen.set(true);
    setTimeout(() => this.lightboxCloseBtn?.nativeElement.focus(), 0);
  }

  closeLightbox(): void {
    this.lightboxOpen.set(false);
    this.previousFocus?.focus();
    this.previousFocus = null;
  }

  prevPhoto(): void {
    const len = this.annonce()?.photos.length ?? 0;
    if (len <= 1) return;
    this.lightboxIndex.update((i) => (i - 1 + len) % len);
  }

  nextPhoto(): void {
    const len = this.annonce()?.photos.length ?? 0;
    if (len <= 1) return;
    this.lightboxIndex.update((i) => (i + 1) % len);
  }

  @HostListener('document:keydown', ['$event'])
  onKeydown(event: KeyboardEvent): void {
    if (!this.lightboxOpen()) return;
    if (event.key === 'Tab') {
      this.trapFocus(event);
    } else if (event.key === 'Escape') {
      event.preventDefault();
      this.closeLightbox();
    } else if (event.key === 'ArrowLeft') {
      event.preventDefault();
      this.prevPhoto();
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      this.nextPhoto();
    }
  }

  /** Garde le focus clavier dans la visionneuse (Tab / Maj+Tab bouclent). */
  private trapFocus(event: KeyboardEvent): void {
    const panel = this.lightboxPanel?.nativeElement;
    if (!panel) return;
    const focusables = Array.from(
      panel.querySelectorAll<HTMLElement>('button:not([disabled]), [href], [tabindex]:not([tabindex="-1"])'),
    );
    if (!focusables.length) return;
    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement as HTMLElement | null;
    if (event.shiftKey && (active === first || !panel.contains(active))) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && (active === last || !panel.contains(active))) {
      event.preventDefault();
      first.focus();
    }
  }

  /** Date du jour au format AAAA-MM-JJ en heure locale (toISOString() est en UTC). */
  private localIso(d: Date): string {
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  }

  private startOfMonth(d: Date): Date {
    return new Date(d.getFullYear(), d.getMonth(), 1);
  }

  private buildMonth(firstDay: Date): CalendarMonth {
    const year = firstDay.getFullYear();
    const month = firstDay.getMonth();
    const todayIso = this.localIso(new Date());
    const blocked = this.unavailableDates();

    // Lundi = 0 ... Dimanche = 6
    const startOffset = (firstDay.getDay() + 6) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const days: CalendarDay[] = [];
    for (let i = 0; i < startOffset; i++) {
      days.push({ iso: `empty-${i}`, day: null, disabled: true, unavailable: false });
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const mm = String(month + 1).padStart(2, '0');
      const dd = String(day).padStart(2, '0');
      const iso = `${year}-${mm}-${dd}`;
      const isPast = iso < todayIso;
      const isBlocked = blocked.has(iso);
      days.push({
        iso,
        day,
        disabled: isPast || isBlocked,
        unavailable: isBlocked,
      });
    }

    const label = firstDay.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    return {
      label: label.charAt(0).toUpperCase() + label.slice(1),
      days,
    };
  }
}
