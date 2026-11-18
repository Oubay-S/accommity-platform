import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AnnonceResume } from '../../models/annonce.model';
import { CurrencyMadPipe } from '../../pipes/currency-mad.pipe';
import { RatingStarsComponent } from '../rating-stars/rating-stars.component';

@Component({
  selector: 'app-annonce-card',
  standalone: true,
  imports: [RouterLink, CurrencyMadPipe, RatingStarsComponent],
  templateUrl: './annonce-card.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './annonce-card.component.scss',
})
export class AnnonceCardComponent {
  @Input({ required: true }) annonce!: AnnonceResume;

  get typeLabel(): string {
    const t = this.annonce.type_logement || 'Hébergement';
    return t.charAt(0).toUpperCase() + t.slice(1);
  }

  get locationLabel(): string {
    return this.annonce.ville || this.annonce.region || 'Maroc';
  }

  onFavoriteClick(event: Event): void {
    event.preventDefault();
    event.stopPropagation();
  }
}
