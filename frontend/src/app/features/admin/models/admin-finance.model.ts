export type AdminFinanceType = 'paiements' | 'versements' | 'remboursements';

export interface AdminTransactionItem {
  // Champs communs
  id_paiement?: string;
  id_versement?: string;
  id_remboursement?: string;
  id_reservation: string;
  montant: number | string;
  statut?: string;

  // Paiement
  date_transaction?: string;
  methode_paiement?: string;

  // Versement
  date_versement?: string | null;
  date_liberation_prevue?: string | null;
  reference_bancaire?: string | null;

  // Remboursement
  date_remboursement?: string;
  taux_applique?: number | string;
  motif?: string;

  // Relations
  voyageur?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
  } | null;
  hote?: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
  } | null;
  reservation?: {
    id_reservation: string;
    annonce?: {
      id_annonce: string;
      titre: string;
    } | null;
  } | null;
}

export interface AdminTransactionPage {
  items: AdminTransactionItem[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
