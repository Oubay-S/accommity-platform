export type StatutReservation =
  | 'en_attente'
  | 'confirmee'
  | 'en_cours'
  | 'terminee'
  | 'annulee'
  | 'refusee'
  | 'expiree';

export type ModeReservation = 'instantanee' | 'sur_demande';

export type ActeurAnnulation = 'voyageur' | 'hote' | 'systeme';

export interface Minuterie {
  id_minuterie: string;
  id_reservation: string;
  type_minuterie: 'expiration_demande' | 'autre';
  date_echeance: string;
  date_expiration?: string;
  est_active: boolean;
}

export interface ReservationAnnonce {
  id_annonce: string;
  titre: string;
  type_logement?: string;
  adresse?: string;
  ville?: string | null;
  photo_principale?: string | null;
  tarif_nuit?: string | number;
}

export interface ReservationParticipant {
  id_utilisateur: string;
  nom: string;
  prenom: string;
  email: string;
  telephone?: string | null;
  photo_url?: string | null;
}

export interface ReservationPolitique {
  type_politique: 'flexible' | 'moderee' | 'stricte';
  description?: string;
}

export interface ReservationPaiement {
  id_paiement: string;
  montant: number;
  methode_paiement: string;
  statut: 'en_attente' | 'reussi' | 'echoue' | 'rembourse';
  date_transaction?: string;
}

export interface Reservation {
  id_reservation: string;
  id_annonce: string;
  id_voyageur: string;
  id_hote: string;
  id_politique: string | null;
  date_arrivee: string;
  date_depart: string;
  nb_voyageurs: number;
  statut: StatutReservation;
  mode_reservation: ModeReservation;
  montant_total: number;
  frais_service: number;
  message_optionnel?: string | null;
  motif_refus?: string | null;
  motif_annulation?: string | null;
  acteur_annulation?: ActeurAnnulation | null;
  date_confirmation?: string | null;
  date_annulation?: string | null;
  date_creation: string;
  date_modification?: string | null;

  // Relations optionnelles chargées par l'API
  annonce?: ReservationAnnonce | null;
  voyageur?: ReservationParticipant | null;
  hote?: ReservationParticipant | null;
  politique?: ReservationPolitique | null;
  minuterie?: Minuterie | null;
  paiement?: ReservationPaiement | null;
}

export interface ReservationCreateDto {
  id_annonce: string;
  date_arrivee: string;
  date_depart: string;
  nb_voyageurs: number;
  message_optionnel?: string;
}

export interface RepondreReservationDto {
  acceptee?: boolean;
  decision?: 'accepter' | 'refuser';
  action?: 'accepter' | 'refuser';
  motif_refus?: string;
  motif?: string;
}

export interface AnnulerReservationDto {
  motif?: string;
  motif_annulation?: string;
}
