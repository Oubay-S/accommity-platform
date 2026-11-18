export interface AdminAvis {
  id_evaluation: string;
  note: number;
  commentaire: string;
  est_signale: boolean;
  motif_signalement?: string | null;
  date_creation: string;
  auteur?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
    profil?: { photo_url?: string | null } | null;
  } | null;
  cible?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
    profil?: { photo_url?: string | null } | null;
  } | null;
  annonce?: {
    id_annonce: string;
    titre: string;
  } | null;
  reservation?: {
    id_reservation: string;
    annonce?: { id_annonce: string; titre: string } | null;
  } | null;
}

export interface AdminAvisPage {
  items: AdminAvis[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
