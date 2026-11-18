import { Pagination } from './api-response.model';

export interface EvaluationAuteur {
  id_utilisateur: string;
  prenom: string;
  nom: string;
  photo_url?: string | null;
}

export interface NotesDetaillees {
  proprete: number;
  communication: number;
  emplacement: number;
  rapport_qualite_prix: number;
  exactitude: number;
}

export interface Evaluation {
  id_evaluation: string;
  note: number;
  commentaire: string;
  date_creation: string;
  auteur: EvaluationAuteur | null;
  cible?: EvaluationAuteur | null;
  annonce?: { id_annonce: string; titre: string } | null;
  notes_detaillees: NotesDetaillees | null;
}

export interface EvaluationStatistiques {
  note_moyenne: number;
  nb_avis: number;
  repartition: Record<string, number>;
}

export interface EvaluationPage {
  items: Evaluation[];
  pagination: Pagination;
  statistiques: EvaluationStatistiques;
}

export interface EvaluationDonneesPage {
  items: Evaluation[];
  pagination: Pagination;
}

export interface EvaluationCreatePayload {
  id_reservation: string;
  note: number;
  commentaire: string;
  notes_detaillees?: NotesDetaillees;
}

export interface EvaluationSignalementPayload {
  motif: string;
}

export interface TicketLitigeSignalement {
  id_ticket: string;
  statut: 'en_cours' | 'cloture';
  date_creation: string;
}

export interface ReservationAEvaluer {
  id_reservation: string;
  role: 'voyageur' | 'hote';
  date_arrivee: string;
  date_depart: string;
  annonce: {
    id_annonce: string;
    titre: string;
    photo_principale: string | null;
  } | null;
  cible: EvaluationAuteur | null;
}
