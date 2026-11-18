export interface AdminLitige {
  id_ticket: string;
  motif: string;
  statut: 'en_cours' | 'cloture';
  date_creation: string;
  date_cloture?: string | null;
  declarant?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
    profil?: { photo_url?: string | null } | null;
  } | null;
  evaluation?: {
    id_evaluation: string;
    commentaire: string;
    cible?: { id_utilisateur: string; nom: string; prenom: string; email: string } | null;
    annonce?: { id_annonce: string; titre: string } | null;
  } | null;
  reservation?: {
    id_reservation: string;
    date_arrivee: string;
    date_depart: string;
    annonce?: { id_annonce: string; titre: string } | null;
    voyageur?: { nom: string; prenom: string; email: string } | null;
    hote?: { nom: string; prenom: string; email: string } | null;
  } | null;
  admin?: {
    id_admin: string;
    utilisateur?: { nom: string; prenom: string } | null;
  } | null;
}

export interface AdminLitigePage {
  items: AdminLitige[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
