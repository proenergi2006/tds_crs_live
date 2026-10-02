import type { EnumOption, LogisticDocument } from "../types";

export type TransporterOwnership = "OWN" | "THIRDPARTY";
export type TransportCapability = "VESSEL" | "TRUCK" | "VESSEL_TRUCK";

export interface Transporter {
  id: number;
  company_name: string;
  short_name: string | null;
  ownership: EnumOption<TransporterOwnership> | null;
  terms: string | null;
  cabang: { id: number; nama_cabang: string } | null;
  address: string | null;
  phone: string | null;
  fax: string | null;
  transport_capability: EnumOption<TransportCapability> | null;
  is_active: boolean;
  email: string | null;
  mobile_phone: string | null;
  note: string | null;
  personnels_count?: number;
  vessels_count?: number;
  trucks_count?: number;
  logistic_documents?: LogisticDocument[];
  created_at: string;
  updated_at: string;
}

export interface TransporterDependents {
  personnels: number;
  vessels: number;
  trucks: number;
}
