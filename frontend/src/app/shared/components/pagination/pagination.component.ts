import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy } from '@angular/core';
import { Pagination } from '../../models/api-response.model';

@Component({
  selector: 'app-pagination',
  standalone: true,
  imports: [],
  templateUrl: './pagination.component.html',
  changeDetection: ChangeDetectionStrategy.Default,
  styleUrl: './pagination.component.scss',
})
export class PaginationComponent {
  @Input({ required: true }) pagination!: Pagination;
  @Output() pageChange = new EventEmitter<number>();

  get pages(): (number | '...')[] {
    const total = this.pagination?.last_page ?? 1;
    const current = this.pagination?.current_page ?? 1;

    if (total <= 7) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }

    const result: (number | '...')[] = [1];
    if (current > 3) {
      result.push('...');
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let p = start; p <= end; p++) {
      result.push(p);
    }

    if (current < total - 2) {
      result.push('...');
    }
    result.push(total);

    return result;
  }

  goTo(page: number): void {
    if (page >= 1 && page <= this.pagination.last_page && page !== this.pagination.current_page) {
      this.pageChange.emit(page);
    }
  }
}
