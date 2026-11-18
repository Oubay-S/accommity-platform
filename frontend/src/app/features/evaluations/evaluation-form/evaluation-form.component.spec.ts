import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { ReservationAEvaluer } from '../../../shared/models/evaluation.model';
import { EvaluationFormComponent } from './evaluation-form.component';
import { EvaluationService } from '../services/evaluation.service';

describe('EvaluationFormComponent', () => {
  let fixture: ComponentFixture<EvaluationFormComponent>;
  let component: EvaluationFormComponent;
  let evaluationService: jasmine.SpyObj<EvaluationService>;
  let toast: jasmine.SpyObj<ToastService>;

  const reservation: ReservationAEvaluer = {
    id_reservation: 'resa-1',
    role: 'voyageur',
    date_arrivee: '2026-11-01',
    date_depart: '2026-11-04',
    annonce: { id_annonce: 'annonce-1', titre: 'Riad de charme', photo_principale: null },
    cible: { id_utilisateur: 'hote-1', prenom: 'Karim', nom: 'Benali', photo_url: null },
  };

  beforeEach(async () => {
    evaluationService = jasmine.createSpyObj('EvaluationService', ['aEvaluer', 'creer']);
    evaluationService.aEvaluer.and.returnValue(of({ items: [reservation] }));
    evaluationService.creer.and.returnValue(
      of({
        evaluation: {
          id_evaluation: 'eval-1',
          note: 5,
          commentaire: 'Très bon séjour.',
          date_creation: '2026-11-04T10:00:00+00:00',
          auteur: null,
          notes_detaillees: null,
        },
      }),
    );

    toast = jasmine.createSpyObj('ToastService', ['success']);

    await TestBed.configureTestingModule({
      imports: [EvaluationFormComponent],
      providers: [
        provideRouter([]),
        { provide: EvaluationService, useValue: evaluationService },
        { provide: ToastService, useValue: toast },
        {
          provide: ActivatedRoute,
          useValue: { snapshot: { paramMap: convertToParamMap({ reservationId: 'resa-1' }) } },
        },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(EvaluationFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('charge le séjour à évaluer', () => {
    expect(component.reservation()).toEqual(reservation);
    expect(component.estVoyageur()).toBeTrue();
    expect(component.loading()).toBeFalse();
  });

  it('refuse un formulaire incomplet', () => {
    component.soumettre();

    expect(evaluationService.creer).not.toHaveBeenCalled();
    expect(component.noteControl.invalid).toBeTrue();
    expect(component.commentaireControl.invalid).toBeTrue();
  });

  it('publie un avis complet avec les notes détaillées', () => {
    component.setNote(5);
    component.commentaireControl.setValue('Un séjour vraiment excellent.');
    component.setCritere('proprete', 5);
    component.setCritere('communication', 5);
    component.setCritere('emplacement', 4);
    component.setCritere('rapport_qualite_prix', 5);
    component.setCritere('exactitude', 5);

    component.soumettre();

    expect(evaluationService.creer).toHaveBeenCalledWith({
      id_reservation: 'resa-1',
      note: 5,
      commentaire: 'Un séjour vraiment excellent.',
      notes_detaillees: {
        proprete: 5,
        communication: 5,
        emplacement: 4,
        rapport_qualite_prix: 5,
        exactitude: 5,
      },
    });
    expect(toast.success).toHaveBeenCalled();
    expect(component.submitting()).toBeFalse();
  });
});
