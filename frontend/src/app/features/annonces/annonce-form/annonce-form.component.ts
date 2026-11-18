import {
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnDestroy,
  Output,
  SimpleChanges,
  inject,
  ChangeDetectionStrategy
} from '@angular/core';

import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AnnonceHote, AnnonceHotePayload, AnnonceReferences } from '../../../shared/models/annonce.model';
import { ApiError } from '../../../shared/models/api-response.model';

export interface AnnonceFormSubmit {
  payload: AnnonceHotePayload;
  photos: File[];
}

const EQUIPEMENTS_SUGGESTIONS = [
  'wifi',
  'cuisine',
  'parking',
  'piscine',
  'climatisation',
  'chauffage',
  'lave-linge',
  'television',
  'terrasse',
  'jardin',
  'vue mer',
  'petit-dejeuner',
];

@Component({
  selector: 'app-annonce-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './annonce-form.component.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './annonce-form.component.scss',
})
export class AnnonceFormComponent implements OnChanges, OnDestroy {
  private readonly fb = inject(FormBuilder);

  @Input() mode: 'create' | 'edit' = 'create';
  @Input() references: AnnonceReferences | null = null;
  @Input() initial: AnnonceHote | null = null;
  @Input() submitting = false;
  @Input() serverErrors: Record<string, string[]> | null = null;

  @Output() readonly save = new EventEmitter<AnnonceFormSubmit>();
  @Output() readonly removePhoto = new EventEmitter<string>();
  @Output() readonly photosAdded = new EventEmitter<File[]>();

  readonly suggestions = EQUIPEMENTS_SUGGESTIONS;
  readonly maxPhotos = 10;

  readonly form = this.fb.group({
    titre: ['', [Validators.required, Validators.maxLength(255)]],
    description: ['', [Validators.required, Validators.maxLength(5000)]],
    type_logement: ['', [Validators.required]],
    id_categorie: ['', [Validators.required]],
    adresse: ['', [Validators.required, Validators.maxLength(255)]],
    capacite: [4, [Validators.required, Validators.min(1), Validators.max(50)]],
    tarif_nuit: [500, [Validators.required, Validators.min(50)]],
    mode_reservation: ['instantanee' as 'instantanee' | 'sur_demande', [Validators.required]],
    id_politique: ['', [Validators.required]],
    equipements: [<string[]>[], [Validators.maxLength(20)]],
    reglement_interieur: ['', [Validators.maxLength(2000)]],
  });

  selectedPhotos: File[] = [];
  photoPreviews: string[] = [];
  photoError: string | null = null;
  existingPhotos: string[] = [];

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['initial'] && this.initial) {
      this.existingPhotos = [...this.initial.photos];
      this.form.patchValue({
        titre: this.initial.titre,
        description: this.initial.description,
        type_logement: this.initial.type_logement,
        id_categorie: this.initial.categorie?.id_categorie ?? '',
        adresse: this.initial.adresse,
        capacite: this.initial.capacite,
        tarif_nuit: Number(this.initial.tarif_nuit),
        mode_reservation: this.initial.mode_reservation,
        id_politique: this.initial.politique_annulation?.id_politique ?? '',
        equipements: [...this.initial.equipements],
        reglement_interieur: this.initial.reglement_interieur ?? '',
      });
    }
  }

  ngOnDestroy(): void {
    this.revokePreviews();
  }

  get equipements(): string[] {
    return this.form.controls.equipements.value ?? [];
  }

  get totalPhotos(): number {
    return this.existingPhotos.length + this.selectedPhotos.length;
  }

  hasEquipement(equipement: string): boolean {
    return this.equipements.some((item) => item.toLowerCase() === equipement.toLowerCase());
  }

  toggleEquipement(equipement: string): void {
    const actuels = this.equipements;
    const next = this.hasEquipement(equipement)
      ? actuels.filter((item) => item.toLowerCase() !== equipement.toLowerCase())
      : [...actuels, equipement];

    this.form.controls.equipements.setValue(next);
  }

  onPhotosSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);

    this.photoError = null;

    for (const file of files) {
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        this.photoError = 'Formats acceptés : JPG, PNG ou WEBP.';
        continue;
      }

      if (file.size > 5 * 1024 * 1024) {
        this.photoError = 'Chaque photo doit peser moins de 5 Mo.';
        continue;
      }

      if (this.totalPhotos >= this.maxPhotos) {
        this.photoError = 'Vous pouvez ajouter au maximum 10 photos.';
        break;
      }

      this.selectedPhotos.push(file);
      this.photoPreviews.push(URL.createObjectURL(file));
    }

    input.value = '';
    this.photosAdded.emit([...this.selectedPhotos]);
  }

  removeSelectedPhoto(index: number): void {
    URL.revokeObjectURL(this.photoPreviews[index]);
    this.selectedPhotos.splice(index, 1);
    this.photoPreviews.splice(index, 1);
    this.photosAdded.emit([...this.selectedPhotos]);
  }

  removeExistingPhoto(url: string): void {
    this.removePhoto.emit(url);
  }

  setExistingPhotos(photos: string[]): void {
    this.existingPhotos = [...photos];
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const valeur = this.form.getRawValue();

    const payload: AnnonceHotePayload = {
      titre: (valeur.titre ?? '').trim(),
      description: (valeur.description ?? '').trim(),
      type_logement: valeur.type_logement ?? '',
      adresse: (valeur.adresse ?? '').trim(),
      id_categorie: valeur.id_categorie ?? '',
      id_politique: valeur.id_politique ?? '',
      capacite: Number(valeur.capacite),
      tarif_nuit: Number(valeur.tarif_nuit),
      mode_reservation: valeur.mode_reservation ?? 'instantanee',
      equipements: valeur.equipements ?? [],
      reglement_interieur: (valeur.reglement_interieur ?? '').trim() || null,
    };

    this.save.emit({ payload, photos: [...this.selectedPhotos] });
  }

  errorFor(field: string): string | null {
    const serveur = this.serverErrors?.[field]?.[0];
    if (serveur) {
      return serveur;
    }

    const controle = this.form.get(field);
    if (!controle || !controle.touched || controle.valid) {
      return null;
    }

    if (controle.hasError('required')) {
      return 'Ce champ est obligatoire.';
    }
    if (controle.hasError('min')) {
      return 'La valeur est trop petite.';
    }
    if (controle.hasError('maxlength')) {
      return 'Le texte est trop long.';
    }

    return 'Valeur invalide.';
  }

  libelleType(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
  }

  private revokePreviews(): void {
    for (const url of this.photoPreviews) {
      URL.revokeObjectURL(url);
    }
    this.photoPreviews = [];
  }

  static messageErreur(error: unknown): string {
    if (error instanceof ApiError) {
      return error.message;
    }
    return 'Une erreur est survenue. Réessayez.';
  }
}
