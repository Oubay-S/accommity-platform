export interface AdminAnnonce {
  id_annonce: string;
  titre: string;
  description: string;
  type_logement: string;
  adresse: string;
  capacite: number;
  tarif_nuit: number;
  statut: 'en_attente' | 'publiee' | 'suspendue' | 'desactivee' | 'rejetee';
  motif_moderation?: string | null;
  photo_url?: string[] | string | null;
  equipements?: string | null;
  reglement_interieur?: string | null;
  date_creation: string;
  hote?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
    telephone?: string;
    profil?: { photo_url?: string | null } | null;
    verification_identite?: { statut: string } | null;
  } | null;
  categorie?: {
    ville: string;
    region: string;
    pays: string;
  } | null;
  politique_annulation?: {
    type_politique: string;
  } | null;
}

export interface AdminAnnoncePage {
  items: AdminAnnonce[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
