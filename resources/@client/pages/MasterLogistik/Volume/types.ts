export interface SatuanRef {
  id_satuan: number;
  nama_satuan: string;
}

export interface Volume {
  id: number;
  volume: number;
  id_satuan: number;
  is_active: boolean;
  satuan: SatuanRef | null;
  created_at: string;
  updated_at: string;
}
