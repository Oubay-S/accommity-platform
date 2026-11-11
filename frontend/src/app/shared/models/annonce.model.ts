export interface Annonce {
  id: number;
  hote_id: number;
  titre: string;
  description: string;
  type_logement: string;
  adresse: string;
  ville: string;
  prix_par_nuit: number;
  capacite_voyageurs: number;
  nombre_chambres: number;
  nombre_lits: number;
  nombre_salles_bain: number;
  mode_reservation: 'instantanee' | 'sur_demande';
  statut: 'en_attente' | 'publiee' | 'suspendue' | 'rejetee';
  photos?: string[];
  note_moyenne?: number;
  nombre_avis?: number;
}

export interface DisponibiliteCalendrier {
  date: string;
  est_disponible: boolean;
  prix_specifique?: number;
}
