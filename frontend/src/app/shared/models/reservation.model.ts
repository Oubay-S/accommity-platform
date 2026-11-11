export interface Reservation {
  id: number;
  voyageur_id: number;
  annonce_id: number;
  date_arrivee: string;
  date_depart: string;
  nombre_voyageurs: number;
  prix_total: number;
  frais_service: number;
  statut: 'en_attente' | 'confirmee' | 'refusee' | 'annulee' | 'terminee' | 'expiree';
  motif_refus?: string;
  annonce?: any;
}

export interface ReservationCreateDto {
  annonce_id: number;
  date_arrivee: string;
  date_depart: string;
  nombre_voyageurs: number;
}
