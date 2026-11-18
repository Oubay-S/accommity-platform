export interface Annonce {
  id_annonce: string;
  id_hote: string;
  titre: string;
  description: string;
  type_logement: string;
  adresse: string;
  ville?: string;
  tarif_nuit: number | string;
  capacite: number;
  mode_reservation: 'instantanee' | 'sur_demande';
  statut: 'en_attente' | 'publiee' | 'suspendue' | 'desactivee' | 'rejetee';
  photo_url?: string[];
  note_moyenne?: number;
  nb_avis?: number;
}

export interface AnnonceResume {
  id_annonce: string;
  titre: string;
  type_logement: string;
  capacite: number;
  mode_reservation: 'instantanee' | 'sur_demande';
  tarif_nuit: string;
  ville: string | null;
  region: string | null;
  photo_principale: string | null;
  note_moyenne: number;
  nb_avis: number;
}

export interface PolitiqueAnnulationResume {
  type_politique: 'flexible' | 'moderee' | 'stricte';
  description: string;
  delai_remb_total: number;
  delai_remb_partiel: number;
  taux_remb_partiel: number;
}

export interface HotePublic {
  id_utilisateur: string;
  prenom: string;
  nom: string;
  photo_url: string | null;
  date_creation: string | null;
}

export interface AnnonceDetail extends AnnonceResume {
  description: string;
  adresse: string;
  reglement_interieur: string | null;
  photos: string[];
  equipements: string[];
  politique_annulation: PolitiqueAnnulationResume | null;
  hote: HotePublic | null;
  date_creation: string | null;
}

export interface CriteresRecherche {
  ville?: string;
  id_categorie?: string;
  type_logement?: string;
  voyageurs?: number;
  date_arrivee?: string;
  date_depart?: string;
  prix_min?: number;
  prix_max?: number;
  equipements?: string[];
  tri?: 'recent' | 'prix_asc' | 'prix_desc';
  page?: number;
  per_page?: number;
}

export interface DisponibiliteCalendrier {
  date: string;
  est_disponible: boolean;
  prix_specifique?: number;
}
