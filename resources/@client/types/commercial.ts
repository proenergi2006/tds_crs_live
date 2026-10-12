export type QuotationBrand = 'tds' | 'proenergi'

export type QuotationStatus = 'draft' | 'waiting_verification' | 'approved' | 'rejected'

export type TransportBasis = 'loco' | 'franco'

export type TransportMode = 'tariff' | 'pickup'

export type TransportLegType = 'VESSEL' | 'TRUCK'

export interface QuotationTransport {
  id: number
  transport_type: TransportLegType
  transport_tariff_id: number
  transporter_id: number | null
  transport_area_id: number | null
  volume_id: number | null
  transporter: { company_name: string } | null
  transport_area: {
    name: string
    provinsi: { nama_provinsi: string } | null
    kabupaten: { nama_kabupaten: string } | null
    province: { name: string } | null
    regency: { name: string } | null
  } | null
  volume: { id: number; volume: number } | null
  price: string | null
  rate?: string | null
  margin?: string | null
}

export interface QuotationItemLine {
  product_id: string
  source_branch_id: string
  product_price_id: number | null
  source_name: string
  quantity: number
  unit_price: number
  harga_price_list: number
  cogs_basis: TransportBasis | null
  transport_price: number | null
  saved_transport_price: number | null
}
