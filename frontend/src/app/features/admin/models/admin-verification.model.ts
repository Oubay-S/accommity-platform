import { Pagination } from '../../../shared/models/api-response.model';
import { TypePiece, VerificationStatut } from '../../../shared/models/user.model';

export type AdminVerificationFilter = 'tous' | 'en_cours' | 'validee' | 'rejetee';

export interface AdminVerificationUser {
  id_utilisateur: string;
  nom: string;
  prenom: string;
  email: string;
  telephone: string | null;
  est_hote: boolean;
}

export interface AdminVerification {
  id_verification: string;
  type_piece: TypePiece;
  statut: VerificationStatut;
  date_soumission: string | null;
  date_traitement: string | null;
  motif_rejet: string | null;
  document_disponible: boolean;
  utilisateur?: AdminVerificationUser | null;
}

export interface AdminVerificationStats {
  tous: number;
  en_cours: number;
  validee: number;
  rejetee: number;
}

export interface AdminVerificationPage {
  items: AdminVerification[];
  pagination: Pagination;
  stats: AdminVerificationStats;
}
