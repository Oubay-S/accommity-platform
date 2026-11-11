export interface Notification {
  id: number;
  type: string;
  titre: string;
  message: string;
  data?: any;
  est_lue: boolean;
  created_at: string;
}
