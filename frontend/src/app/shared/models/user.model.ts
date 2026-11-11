export interface User {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  telephone?: string;
  role: 'voyageur' | 'hote' | 'admin';
  is_actif: boolean;
  is_hote_verifie?: boolean;
  profil?: Profil;
}

export interface Profil {
  id: number;
  photo_url?: string;
  bio?: string;
  adresse?: string;
  ville?: string;
  pays?: string;
}

export interface VerificationIdentite {
  id: number;
  type_document: 'cin' | 'passeport';
  numero_document: string;
  document_url: string;
  statut: 'en_attente' | 'validee' | 'rejetee';
  motif_rejet?: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  nom: string;
  prenom: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: 'voyageur' | 'hote';
}
