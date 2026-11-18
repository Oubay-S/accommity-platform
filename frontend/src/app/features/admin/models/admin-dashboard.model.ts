export interface AdminDashboardStats {
  utilisateurs: {
    total: number;
    hotes: number;
    voyageurs: number;
    actifs: number;
    inactifs: number;
    verifications_en_cours: number;
  };
  annonces: {
    total: number;
    publiees: number;
    en_attente: number;
    suspendues: number;
    rejetees: number;
  };
  reservations: {
    total: number;
    en_attente: number;
    confirmees: number;
    en_cours: number;
    terminees: number;
    annulees: number;
    refusees: number;
    expirees: number;
  };
  finances: {
    volume_total_paiements: number;
    nombre_transactions: number;
    volume_versements_hotes: number;
    nombre_versements: number;
    volume_remboursements: number;
    nombre_remboursements: number;
  };
  moderation: {
    avis_signales: number;
    litiges_en_cours: number;
    litiges_clotures: number;
  };
  urgences: {
    annonces_en_attente: Array<{
      id_annonce: string;
      titre: string;
      tarif_nuit: number;
      date_creation: string;
      hote?: { nom: string; prenom: string; email: string };
      categorie?: { ville: string; region: string };
    }>;
    litiges_ouverts: Array<{
      id_ticket: string;
      motif: string;
      date_creation: string;
      declarant?: { nom: string; prenom: string; email: string };
    }>;
  };
}
