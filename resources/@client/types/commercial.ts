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
  customer: { id_customer: number; company_name: string } | null
  marketing: { name: string } | null
  lokasi_pengiriman: string | null
  items: QuotationIndexRowItem[]
  has_po_customer: boolean
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

export type PoCustomerStatusKey = 'awaiting_process' | 'blocked' | 'awaiting_sc' | 'sc_in_progress' | 'done'

export type PoCustomerPaymentType = 'CBD' | 'COD' | 'CREDIT'

export type SalesConfirmationStatus = 1 | 2 | 4 | 5

export interface PoCustomerItem {
  id: number
  quotation_item_id: number
  product: { id: number; name: string; size: string | null; unit: string | null } | null
  source_branch: { id: number; name: string } | null
  quantity_quoted: number
  volume: number
  unit_price: number
  transport_price: number
  final_price: number
  line_total: number
}

export interface PoCustomerTotals {
  volume: number
  material_subtotal: number
  transport_total: number
  dpp: number
  ppn_rate: number
  ppn: number
  grand_total: number
}

export interface PoCustomerCustomerRef {
  id_customer: number
  customer_code: string | null
  company_name: string | null
}

export interface PoCustomerIndexRow {
  id_poc: number
  nomor_poc: string | null
  tanggal_poc: string | null
  supply_date: string | null
  status_key: PoCustomerStatusKey
  status_label: string
  tipe_bayar: PoCustomerPaymentType | null
  tipe_bayar_label: string | null
  customer: PoCustomerCustomerRef | null
  quotation: { id: number; nomor_penawaran: string | null; brand: QuotationBrand | null } | null
  total_volume: number
  total_value: number
  can_update: boolean
}

export interface PoCustomerDetail {
  id_poc: number
  id_customer: number
  quotation_id: number
  nomor_poc: string | null
  tanggal_poc: string | null
  supply_date: string | null
  tipe_bayar: PoCustomerPaymentType | null
  tipe_bayar_label: string | null
  termin_hari: number | null
  lampiran_poc: string | null
  lampiran_poc_ori: string | null
  status_key: PoCustomerStatusKey
  status_label: string
  is_locked: boolean
  sc_process_state: string | null
  created_by: string | null
  created_time: string | null
  customer: PoCustomerCustomerRef | null
  quotation: {
    id: number
    nomor_penawaran: string | null
    brand: QuotationBrand | null
    masa_berlaku: string | null
    sampai_dengan: string | null
    transport_basis: TransportBasis | null
  } | null
  items: PoCustomerItem[]
  totals: PoCustomerTotals
  sales_confirmation: { id: number; disposisi: SalesConfirmationStatus; disposisi_label: string } | null
  can_update: boolean
}

export interface SalesConfirmationIndexRow {
  id_poc: number
  nomor_poc: string | null
  tanggal_poc: string | null
  customer: { customer_code: string | null; company_name: string | null } | null
  quotation: { nomor_penawaran: string | null } | null
  marketing_name: string | null
  disposisi: SalesConfirmationStatus | null
  disposisi_text: string | null
  disposisi_time: string | null
  sc_process_state: string | null
  active_unblock_request: unknown
  total_volume: number
  total_value: number
}

export type SalesConfirmationApprovalStepStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'

export interface SalesConfirmationApprovalStep {
  step_order: number
  step_name: string | null
  status: SalesConfirmationApprovalStepStatus | null
  actor_name: string | null
  acted_at: string | null
  decision_note: string | null
}

export interface SalesConfirmationApprovalFlow {
  current_step_order: number | null
  can_decide: boolean
  steps: SalesConfirmationApprovalStep[]
}

export interface SalesConfirmationAdminAttachment {
  path: string
  original_name: string | null
  url: string | null
  size_bytes: number | null
}

export interface SalesConfirmationDetail {
  poc: {
    id_poc: number
    nomor_poc: string | null
    tanggal_poc: string | null
    supply_date: string | null
    tipe_bayar: PoCustomerPaymentType | null
    tipe_bayar_label: string | null
    termin_hari: number | null
    lampiran_poc: string | null
    lampiran_poc_ori: string | null
    created_by: string | null
    created_time: string | null
    items: PoCustomerItem[]
    totals: PoCustomerTotals
  }
  customer: { id_customer: number; customer_code: string | null; company_name: string | null; credit_limit: number }
  quotation: Record<string, any>
  credit: {
    approved_top: number | null
    nilai_order_ppn: number
    sisa_limit_setelah_order: number
    gate: {
      type: 'cbd_cod' | 'auto' | 'unblock'
      unblock: { requested_by_name: string | null; requested_at: string | null; reason: string | null } | null
    }
  }
  arnya: Record<string, number | null>
  sc: { disposisi: SalesConfirmationStatus; disposisi_label: string; disposisi_time: string }
  sc_header: Record<string, number> | null
  approval: {
    adm_result: number
    adm_summary: string | null
    adm_result_date: string | null
    adm_pic: string | null
    adm_attachments: SalesConfirmationAdminAttachment[]
  } | null
  approval_flow: SalesConfirmationApprovalFlow | null
}

export type UnblockRequestedBy = { id: number; name: string; role_name: string | null }

export type UnblockStep = {
  step_order: number
  step_name: string | null
  status: string | null
  actor_name: string | null
  acted_at: string | null
  decision_note: string | null
}

export type UnblockAttachment = { path: string; original_name: string; size_bytes: number | null }

export type UnblockApproval = {
  current_step_order: number | null
  steps: UnblockStep[]
}

export type UnblockRequest = {
  id: number
  id_poc: number
  reason: string | null
  attachments: UnblockAttachment[]
  status: string | null
  status_label: string | null
  requested_by: UnblockRequestedBy | null
  created_at: string | null
  approval: UnblockApproval | null
}

export type UnblockContextPo = {
  id_poc: number
  nomor_poc: string | null
  customer: { customer_code: string | null; company_name: string | null } | null
  nilai_order: number
  current_credit_limit: number
  exposure: number
  headroom: number
  status_key: string | null
  status_label: string | null
}

export type UnblockContext = { po: UnblockContextPo; active_request: UnblockRequest | null }

export type ArBucketKey =
  | 'outstanding_current'
  | 'overdue_1_30'
  | 'overdue_31_60'
  | 'overdue_61_90'
  | 'overdue_90_plus'
