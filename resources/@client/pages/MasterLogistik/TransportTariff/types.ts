import type { EnumOption, TransporterRef } from "../types";

export type TransportType = "VESSEL" | "TRUCK";

export interface TransportAreaRef {
  id: number;
  name: string;
}

export interface VolumeRef {
  id: number;
  volume: number;
}

export interface TransportTariff {
  id: number;
  transporter: TransporterRef | null;
  transport_type: EnumOption<TransportType> | null;
  transport_area: TransportAreaRef | null;
  volume: VolumeRef | null;
  rate: string;
  note: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}
