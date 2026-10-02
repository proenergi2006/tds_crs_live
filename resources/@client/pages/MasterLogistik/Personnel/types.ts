import type { LogisticDocument, TransporterRef } from "../types";

export interface Personnel {
  id: number;
  transporter: TransporterRef | null;
  name: string;
  photo_url: string | null;
  is_active: boolean;
  logistic_documents_count: number;
  logistic_documents?: LogisticDocument[];
  created_at: string;
  updated_at: string;
}
