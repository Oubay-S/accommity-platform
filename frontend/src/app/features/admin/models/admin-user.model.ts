export interface AdminUser {
  id_utilisateur: string;
  nom: string;
  prenom: string;
  email: string;
  telephone?: string | null;
  est_hote: boolean;
  est_voyageur: boolean;
  est_actif: boolean;
  date_creation: string;
  profil?: {
    photo_url?: string | null;
    note_moyenne?: number;
    description?: string | null;
  } | null;
  verification_identite?: {
    id_verification?: string;
    statut: string;
    type_piece: string;
    date_soumission?: string;
    motif_rejet?: string | null;
  } | null;
}

export interface AdminUserPage {
  items: AdminUser[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
