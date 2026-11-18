import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import {
  HttpTestingController,
  provideHttpClientTesting,
} from '@angular/common/http/testing';
import { environment } from '../../../../environments/environment';
import { EvaluationService } from './evaluation.service';

describe('EvaluationService', () => {
  let service: EvaluationService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [EvaluationService, provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(EvaluationService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('charge les avis publics d’une annonce avec pagination', () => {
    service.listerPourAnnonce('annonce-1', { page: 2, per_page: 6 }).subscribe((page) => {
      expect(page.pagination.current_page).toBe(2);
      expect(page.items.length).toBe(1);
    });

    const requete = http.expectOne(
      (req) =>
        req.url === `${environment.apiUrl}/annonces/annonce-1/avis` &&
        req.params.get('page') === '2' &&
        req.params.get('per_page') === '6',
    );

    expect(requete.request.method).toBe('GET');
    requete.flush({
      success: true,
      message: 'ok',
      data: {
        items: [
          {
            id_evaluation: 'eval-1',
            note: 5,
            commentaire: 'Excellent séjour.',
            date_creation: '2026-11-01T10:00:00+00:00',
            auteur: null,
            notes_detaillees: null,
          },
        ],
        pagination: { total: 7, per_page: 6, current_page: 2, last_page: 2 },
        statistiques: { note_moyenne: 4.8, nb_avis: 7, repartition: { '5': 7 } },
      },
    });
  });

  it('publie un avis', () => {
    const payload = {
      id_reservation: 'resa-1',
      note: 5,
      commentaire: 'Un très bon séjour.',
      notes_detaillees: {
        proprete: 5,
        communication: 5,
        emplacement: 4,
        rapport_qualite_prix: 5,
        exactitude: 5,
      },
    };

    service.creer(payload).subscribe((reponse) => {
      expect(reponse.evaluation.id_evaluation).toBe('eval-1');
    });

    const requete = http.expectOne(`${environment.apiUrl}/evaluations`);
    expect(requete.request.method).toBe('POST');
    expect(requete.request.body).toEqual(payload);
    requete.flush({
      success: true,
      message: 'Avis publié.',
      data: {
        evaluation: {
          id_evaluation: 'eval-1',
          note: 5,
          commentaire: payload.commentaire,
          date_creation: '2026-11-01T10:00:00+00:00',
          auteur: null,
          notes_detaillees: payload.notes_detaillees,
        },
      },
    });
  });

  it('signale un avis', () => {
    service.signaler('eval-1', { motif: 'Contenu inapproprié.' }).subscribe((reponse) => {
      expect(reponse.ticket.statut).toBe('en_cours');
    });

    const requete = http.expectOne(`${environment.apiUrl}/evaluations/eval-1/signaler`);
    expect(requete.request.method).toBe('POST');
    requete.flush({
      success: true,
      message: 'Avis signalé.',
      data: {
        ticket: {
          id_ticket: 'ticket-1',
          statut: 'en_cours',
          date_creation: '2026-11-01T10:00:00+00:00',
        },
      },
    });
  });

  it('charge les séjours à évaluer et les avis reçus', () => {
    service.aEvaluer().subscribe((reponse) => expect(reponse.items.length).toBe(0));
    http.expectOne(`${environment.apiUrl}/evaluations/a-evaluer`).flush({
      success: true,
      message: 'ok',
      data: { items: [] },
    });

    service.recues({ per_page: 3 }).subscribe((page) => expect(page.statistiques.nb_avis).toBe(0));
    const requete = http.expectOne(
      (req) =>
        req.url === `${environment.apiUrl}/evaluations/recues` &&
        req.params.get('per_page') === '3',
    );
    requete.flush({
      success: true,
      message: 'ok',
      data: {
        items: [],
        pagination: { total: 0, per_page: 3, current_page: 1, last_page: 1 },
        statistiques: { note_moyenne: 0, nb_avis: 0, repartition: {} },
      },
    });
  });
});
