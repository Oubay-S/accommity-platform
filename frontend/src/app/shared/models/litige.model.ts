export interface TicketLitige {
  id: number;
  plaignant_id: number;
  reservation_id?: number;
  objet: string;
  description: string;
  statut: 'ouvert' | 'en_cours' | 'resolu' | 'ferme';
  resolution?: string;
}
