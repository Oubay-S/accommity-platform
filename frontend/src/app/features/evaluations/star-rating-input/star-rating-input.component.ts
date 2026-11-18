import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
  selector: 'app-star-rating-input',
  standalone: true,
  imports: [],
  templateUrl: './star-rating-input.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './star-rating-input.component.scss',
})
export class StarRatingInputComponent {
  @Input() label = 'Note';
  @Input() value = 0;
  @Input() disabled = false;

  @Output() readonly valueChange = new EventEmitter<number>();

  readonly etoiles = [1, 2, 3, 4, 5];

  selectionner(note: number): void {
    if (this.disabled) {
      return;
    }

    this.value = note;
    this.valueChange.emit(note);
  }

  ariaLabel(note: number): string {
    return `${this.label} : ${note} sur 5`;
  }
}
