import { Reservation } from './reservation.model';

export type StatutPaiement = 'en_attente' | 'reussi' | 'echoue' | 'rembourse';

export type MethodePaiement = 'carte_bancaire' | 'paypal' | 'autre';

export type MotifRemboursement = 'annulation_voyageur' | 'annulation_hote' | 'expiration_demande';

export interface Recu {
  id_recu: string;
  id_paiement: string;
  numero_facture: string;
  chemin_pdf?: string | null;
  date_generation: string;
}

export interface Paiement {
  id_paiement: string;
  id_reservation: string;
  id_voyageur: string;
  montant: number;
  methode_paiement: MethodePaiement;
  statut: StatutPaiement;
  date_transaction: string;

  // Relations optionnelles
  recu?: Recu | null;
  reservation?: Reservation | null;
}

export interface RecuDetail extends Recu {
  paiement: Paiement;
  reservation: Reservation;
  montant_total: number;
  frais_service: number;
  montant_hote: number;
  voyageur: {
    id_utilisateur: string;
    nom: string;
    prenom: string;
    email: string;
  };
  annonce: {
    id_annonce: string;
    titre: string;
    adresse: string;
    ville?: string;
    type_logement?: string;
  };
}

export interface Remboursement {
  id_remboursement: string;
  id_reservation: string;
  id_voyageur: string;
  id_paiement: string;
  montant: number;
  taux_applique: number;
  date_remboursement: string;
  motif: MotifRemboursement;
}

export interface ApercuRemboursement {
  montant_paye: number;
  montant_rembourse: number;
  montant_non_remb: number;
  taux_applique: number;
  politique: 'flexible' | 'moderee' | 'stricte' | null;
  description: string | null;
}

export interface DonneesCarte {
  numero_carte: string;
  date_expiration: string;
  cvv: string;
  nom_titulaire?: string;
}

export interface ResultatPaiement {
  id_paiement: string;
  statut: StatutPaiement;
  numero_facture: string;
  id_recu: string;
}
