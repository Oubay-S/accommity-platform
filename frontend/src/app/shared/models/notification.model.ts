import { Pagination } from './api-response.model';

export type TypeAlerte = 'reservation' | 'paiement' | 'avis' | 'rappel' | 'systeme';

export interface Notification {
  id_notification: string;
  titre: string;
  contenu: string;
  type_alerte: TypeAlerte;
  est_lue: boolean;
  date_creation: string | null;
  id_reservation: string | null;
  data: Record<string, unknown> | null;
}

export interface NotificationPage {
  items: Notification[];
  pagination: Pagination;
  non_lues: number;
}
