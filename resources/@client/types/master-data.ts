import type { TransportLegType } from './commercial'

export interface TransportMarginRow {
  id: number
  price_period_id: number
  branch: { id: number; name: string }
  product: { id: number; nama_produk: string; ukuran: string | null }
  cogs_transport_price: string | null
  margin_transport_amount: string | null
  suggested_oat: number | null
  is_ended: boolean
  updated_at: string | null
  updated_by: string | null
}

export interface TransportMarginPeriod {
  id: number
  start_date: string
  end_date: string
  is_ended: boolean
  franco_count: number
  filled_count: number
}

export interface TransportTariffCheck {
  id: number
  transport_type: TransportLegType
  transporter_id: number
  transport_area_id: number
  volume_id: number
  available: boolean
  price: string | null
}
