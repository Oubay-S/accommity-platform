import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-rating-stars',
  standalone: true,
  imports: [],
  templateUrl: './rating-stars.component.html',
  styleUrl: './rating-stars.component.scss',
})
export class RatingStarsComponent {
  @Input() note = 0;
  @Input() taille: 'sm' | 'md' | 'lg' = 'sm';

  readonly stars = [1, 2, 3, 4, 5];

  get roundedNote(): number {
    return Math.round(Math.max(0, Math.min(5, Number(this.note) || 0)));
  }

  get ariaLabel(): string {
    const val = (Number(this.note) || 0).toFixed(1).replace('.', ',');
    return `Note : ${val} sur 5`;
  }
}
