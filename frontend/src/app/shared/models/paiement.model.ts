export interface Paiement {
  id: number;
  reservation_id: number;
  reference_transaction: string;
  montant: number;
  mode_paiement: string;
  statut: 'succes' | 'echec' | 'rembourse';
  date_paiement: string;
}

export interface RecuPaiement {
  numero_recu: string;
  reservation_id: number;
  montant: number;
  date: string;
  pdf_url?: string;
}
