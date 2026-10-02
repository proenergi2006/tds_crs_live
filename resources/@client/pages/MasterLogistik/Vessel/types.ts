import type { LogisticDocument, TransporterRef } from "../types";

export interface Vessel {
  id: number;
  transporter: TransporterRef | null;
  name: string;
  max_capacity: number | null;
  classification: string | null;
  length: string | null;
  width: string | null;
  origin: string | null;
  type: string | null;
  is_active: boolean;
  logistic_documents_count: number;
  logistic_documents?: LogisticDocument[];
  created_at: string;
  updated_at: string;
}
