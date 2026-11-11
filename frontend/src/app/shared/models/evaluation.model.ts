export interface Evaluation {
  id: number;
  auteur_id: number;
  cible_id?: number;
  annonce_id?: number;
  note_globale: number;
  commentaire: string;
  created_at: string;
  notes_detaillees?: {
    proprete: number;
    communication: number;
    emplacement: number;
    qualite_prix: number;
  };
}
