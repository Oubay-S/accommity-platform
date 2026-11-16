import { CurrencyMadPipe } from './currency-mad.pipe';

describe('CurrencyMadPipe', () => {
  let pipe: CurrencyMadPipe;

  beforeEach(() => {
    pipe = new CurrencyMadPipe();
  });

  it('creates an instance', () => {
    expect(pipe).toBeTruthy();
  });

  it('formats numeric values with MAD currency suffix', () => {
    const result = pipe.transform(1200);
    // Supprime les espaces insécables éventuels dans le formatage fr-FR
    expect(result.replace(/\s/g, ' ')).toContain('1 200 MAD');
  });

  it('formats string numbers correctly', () => {
    const result = pipe.transform('500');
    expect(result.replace(/\s/g, ' ')).toContain('500 MAD');
  });

  it('returns 0 MAD when given NaN or invalid values', () => {
    expect(pipe.transform('invalid')).toBe('0 MAD');
  });
});
