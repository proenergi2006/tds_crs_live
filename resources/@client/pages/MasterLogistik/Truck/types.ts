import type { LogisticDocument, TransporterRef } from "../types";

export interface Truck {
  id: number;
  transporter: TransporterRef | null;
  license_plate: string;
  type: string;
  max_capacity: number;
  name: string | null;
  is_active: boolean;
  logistic_documents_count: number;
  logistic_documents?: LogisticDocument[];
  created_at: string;
  updated_at: string;
}
