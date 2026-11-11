import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'currencyMad',
  standalone: true
})
export class CurrencyMadPipe implements PipeTransform {
  transform(value: number | string): string {
    const amount = Number(value);
    return isNaN(amount) ? '0 MAD' : `${amount.toLocaleString('fr-FR')} MAD`;
  }
}
