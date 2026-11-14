export type UserRole = 'voyageur' | 'hote' | 'admin';

export type VerificationStatut = 'en_cours' | 'validee' | 'rejetee';

export type TypePiece = 'cin' | 'passeport';

export interface Profil {
  id_profil: string;
  photo_url: string | null;
  description: string | null;
  note_moyenne: number;
}

export interface VerificationIdentite {
  id_verification: string;
  type_piece: TypePiece;
  statut: VerificationStatut;
  date_soumission: string | null;
  date_traitement: string | null;
  motif_rejet: string | null;
}

export interface User {
  id_utilisateur: string;
  nom: string;
  prenom: string;
  email: string;
  telephone: string | null;
  role: UserRole;
  est_hote: boolean;
  est_voyageur: boolean;
  est_admin: boolean;
  est_actif: boolean;
  est_hote_verifie: boolean;
  date_creation: string | null;
  profil?: Profil | null;
  verification_identite?: VerificationIdentite | null;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface RegisterPayload {
  nom: string;
  prenom: string;
  email: string;
  password: string;
  password_confirmation: string;
  telephone?: string | null;
  role: 'voyageur' | 'hote';
  description?: string | null;
}

export interface AuthData {
  user: User;
  token: string;
  token_type: string;
}
