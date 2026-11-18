export interface AdminReservation {
  id_reservation: string;
  id_annonce: string;
  id_voyageur: string;
  id_hote: string;
  date_arrivee: string;
  date_depart: string;
  nb_voyageurs: number;
  statut: 'en_attente' | 'confirmee' | 'en_cours' | 'terminee' | 'annulee' | 'refusee' | 'expiree';
  mode_reservation: 'instantanee' | 'sur_demande';
  montant_total: number | string;
  frais_service: number | string;
  message_optionnel?: string | null;
  motif_refus?: string | null;
  motif_annulation?: string | null;
  acteur_annulation?: 'voyageur' | 'hote' | 'systeme' | null;
  date_creation: string;
  annonce?: {
    id_annonce: string;
    titre: string;
    adresse?: string;
    type_logement?: string;
  } | null;
  voyageur?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
    profil?: { photo_url?: string | null } | null;
  } | null;
  hote?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
    profil?: { photo_url?: string | null } | null;
  } | null;
}

export interface AdminReservationPage {
  items: AdminReservation[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
