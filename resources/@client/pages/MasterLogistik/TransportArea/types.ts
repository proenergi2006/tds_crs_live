export interface RegionRef {
  id: string;
  name: string;
}

export interface TransportArea {
  id: number;
  name: string;
  is_active: boolean;
  provinsi: { id: number; nama_provinsi: string } | null;
  kabupaten: { id: number; nama_kabupaten: string } | null;
  province: RegionRef | null;
  regency: RegionRef | null;
  created_at: string;
  updated_at: string;
}

export interface TransportAreaDependents {
  transport_tariffs: number;
  customer_lcr: number;
}
