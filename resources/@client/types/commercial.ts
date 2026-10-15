export type QuotationBrand = 'tds' | 'proenergi'

export type QuotationPriceTier = 'tier_1' | 'tier_2' | 'tier_3'

export type QuotationStatus ='draft' | 'waiting_verification' | 'approved' | 'rejected'

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

export interface QuotationIndexRowItem {
  id: number
  quantity: number | string | null
  product: {
    nama_produk: string
    jenis: { nama: string } | null
    ukuran: { nama_ukuran: string } | null
  } | null
}

export interface QuotationIndexRow {
  id: number
  nomor_penawaran: string | null
  keterangan: string | null
  user_id: number
  status: QuotationStatus
  status_label: string | null
  brand: QuotationBrand
  brand_label: string
  masa_berlaku: string | null
  sampai_dengan: string | null
  total_volume: number | string | null
  customer: { company_name: string } | null
  marketing: { name: string } | null
  items: QuotationIndexRowItem[]
  created_at: string
}

export interface QuotationQueueRow {
  id: number
  nomor_penawaran: string | null
  status: QuotationStatus
  brand: QuotationBrand
  brand_label: string
  price_tier: QuotationPriceTier | null
  masa_berlaku: string | null
  sampai_dengan: string | null
  customer: { company_name: string } | null
  branch: { nama_cabang: string } | null
}
