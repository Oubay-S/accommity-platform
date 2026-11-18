import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { EvaluationPage } from '../../../shared/models/evaluation.model';
import { EvaluationListComponent } from './evaluation-list.component';
import { EvaluationService } from '../services/evaluation.service';

describe('EvaluationListComponent', () => {
  let fixture: ComponentFixture<EvaluationListComponent>;
  let component: EvaluationListComponent;
  let evaluationService: jasmine.SpyObj<EvaluationService>;

  const page: EvaluationPage = {
    items: [
      {
        id_evaluation: 'eval-1',
        note: 4,
        commentaire: 'Très bon accueil et logement conforme.',
        date_creation: '2026-11-01T10:00:00+00:00',
        auteur: { id_utilisateur: 'auteur-1', prenom: 'Sara', nom: 'M.', photo_url: null },
        notes_detaillees: {
          proprete: 4,
          communication: 5,
          emplacement: 4,
          rapport_qualite_prix: 4,
          exactitude: 4,
        },
      },
    ],
    pagination: { total: 1, per_page: 6, current_page: 1, last_page: 1 },
    statistiques: { note_moyenne: 4, nb_avis: 1, repartition: { '4': 1 } },
  };

  beforeEach(async () => {
    evaluationService = jasmine.createSpyObj('EvaluationService', ['listerPourAnnonce', 'signaler']);
    evaluationService.listerPourAnnonce.and.returnValue(of(page));
    evaluationService.signaler.and.returnValue(
      of({ ticket: { id_ticket: 'ticket-1', statut: 'en_cours', date_creation: '2026-11-01' } }),
    );

    await TestBed.configureTestingModule({
      imports: [EvaluationListComponent],
      providers: [
        provideRouter([]),
        { provide: EvaluationService, useValue: evaluationService },
        { provide: ToastService, useValue: jasmine.createSpyObj('ToastService', ['success']) },
        {
          provide: AuthService,
          useValue: {
            isAuthenticated: () => true,
            currentUser: () => ({ id_utilisateur: 'utilisateur-1' }),
          },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(EvaluationListComponent);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('annonceId', 'annonce-1');
    fixture.detectChanges();
  });

  it('charge et affiche les avis de l’annonce', () => {
    expect(evaluationService.listerPourAnnonce).toHaveBeenCalledWith('annonce-1', {
      page: 1,
      per_page: 6,
    });
    expect(component.page()?.items.length).toBe(1);
    expect(fixture.nativeElement.textContent).toContain('Très bon accueil');
  });

  it('calcule la répartition et autorise le signalement d’un avis tiers', () => {
    expect(component.repartition()[0]).toEqual({ etoile: 5, nombre: 0, pourcentage: 0 });
    expect(component.peutSignaler(page.items[0])).toBeTrue();
    expect(component.notesDetaillees(page.items[0]).length).toBe(5);
  });
});
