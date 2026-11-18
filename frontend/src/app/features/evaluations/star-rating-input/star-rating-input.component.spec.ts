import { ComponentFixture, TestBed } from '@angular/core/testing';
import { StarRatingInputComponent } from './star-rating-input.component';

describe('StarRatingInputComponent', () => {
  let fixture: ComponentFixture<StarRatingInputComponent>;
  let component: StarRatingInputComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [StarRatingInputComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(StarRatingInputComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('émet la note sélectionnée', () => {
    spyOn(component.valueChange, 'emit');

    const boutons = fixture.nativeElement.querySelectorAll('button');
    boutons[3].click();

    expect(component.value).toBe(4);
    expect(component.valueChange.emit).toHaveBeenCalledWith(4);
  });

  it('n’émet rien quand le composant est désactivé', () => {
    component.disabled = true;
    spyOn(component.valueChange, 'emit');
    fixture.detectChanges();

    const boutons = fixture.nativeElement.querySelectorAll('button');
    boutons[4].click();

    expect(component.valueChange.emit).not.toHaveBeenCalled();
  });
});
