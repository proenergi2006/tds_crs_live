import { formatNumber } from '@/utils/format'
import type {
  PoCustomerItem,
  PoCustomerTotals,
  SalesConfirmationDetail,
  SalesConfirmationQuotation,
  SalesConfirmationQuotationItem,
} from '@/types'

import { VOLUME_UNIT } from './format'

export type PaymentKind = 'CBD' | 'COD' | 'CREDIT'

export interface NormalizedPayment {
  kind: PaymentKind
  days: number | null
}

export type ItemRowState = 'full' | 'partial' | 'untaken'

export interface ItemRow {
  key: string
  name: string
  quotation: SalesConfirmationQuotationItem | null
  po: PoCustomerItem | null
  state: ItemRowState
}

export interface DifferencePart {
  text: string
  strong?: boolean
}

export interface Difference {
  key: string
  topic: string
  parts: DifferencePart[]
}

export interface Comparison {
  rows: ItemRow[]
  differences: Difference[]
  checked: string[]
  paymentDiffers: boolean
  quotationPayment: NormalizedPayment | null
  poPayment: NormalizedPayment | null
}

export interface ScTotals {
  volume: number
  material: number
  transport: number
  dpp: number
  ppnRate: number
  ppn: number
  total: number
}

const VOLUME_TOLERANCE = 0.005

export function normalizeQuotationPayment(
  type: string | null | undefined,
  days: number | string | null | undefined,
): NormalizedPayment | null {
  const value = String(type ?? '').trim().toUpperCase()
  if (value === 'CBD' || value === 'COD') return { kind: value, days: null }

  const top = value.match(/^TOP\s*(\d+)$/)
  if (top) return { kind: 'CREDIT', days: Number(top[1]) }

  if (value === 'CUSTOM') {
    const customDays = Number(days)
    return customDays > 0 ? { kind: 'CREDIT', days: customDays } : null
  }

  return null
}

export function normalizePoPayment(
  type: string | null | undefined,
  days: number | string | null | undefined,
): NormalizedPayment | null {
  if (type === 'CBD' || type === 'COD') return { kind: type, days: null }
  if (type !== 'CREDIT') return null

  const termDays = Number(days)
  return { kind: 'CREDIT', days: termDays > 0 ? termDays : null }
}

export function formatPayment(payment: NormalizedPayment): string {
  if (payment.kind !== 'CREDIT') return payment.kind
  return payment.days ? `Kredit · ${payment.days} hari` : 'Kredit'
}

function paymentsDiffer(quotation: NormalizedPayment | null, po: NormalizedPayment | null): boolean {
  if (!quotation || !po) return false
  if (quotation.kind !== po.kind) return true
  return quotation.days !== null && po.days !== null && quotation.days !== po.days
}

function matchScore(quotation: SalesConfirmationQuotationItem, po: PoCustomerItem): number {
  const sameQuantity = Math.abs(Number(po.quantity_quoted) - Number(quotation.quantity)) < VOLUME_TOLERANCE
  const samePrice = Math.abs(Number(po.unit_price) - Number(quotation.unit_price)) < VOLUME_TOLERANCE
  const sameSize = (po.product?.size ?? '') === (quotation.ukuran ?? '')

  return (sameQuantity ? 4 : 0) + (samePrice ? 2 : 0) + (sameSize ? 1 : 0)
}

// Item Quotation di payload tidak membawa id, jadi pasangannya dicari lewat nama produk lalu qty/harga/ukuran.
function matchPoItem(
  quotation: SalesConfirmationQuotationItem,
  poItems: PoCustomerItem[],
  used: Set<number>,
): PoCustomerItem | null {
  let best: PoCustomerItem | null = null
  let bestScore = -1

  poItems.forEach(po => {
    if (used.has(po.id) || (po.product?.name ?? null) !== (quotation.produk ?? null)) return

    const score = matchScore(quotation, po)
    if (score > bestScore) {
      best = po
      bestScore = score
    }
  })

  return best
}

export function buildItemRows(detail: SalesConfirmationDetail): ItemRow[] {
  const quotationItems = detail.quotation?.items ?? []
  const poItems = detail.poc.items
  const used = new Set<number>()

  const rows = quotationItems.map((quotation, index): ItemRow => {
    const matched = matchPoItem(quotation, poItems, used)
    if (matched) used.add(matched.id)

    const po = matched && Number(matched.volume) > 0 ? matched : null
    const partial = po !== null && Number(po.volume) < Number(quotation.quantity) - VOLUME_TOLERANCE

    return {
      key: `q-${index}`,
      name: quotation.produk || '-',
      quotation,
      po,
      state: po === null ? 'untaken' : partial ? 'partial' : 'full',
    }
  })

  poItems
    .filter(item => Number(item.volume) > 0 && !used.has(item.id))
    .forEach(po => rows.push({ key: `p-${po.id}`, name: po.product?.name || '-', quotation: null, po, state: 'full' }))

  return rows
}

export function buildComparison(detail: SalesConfirmationDetail): Comparison {
  const rows = buildItemRows(detail)
  const quotationPayment = normalizeQuotationPayment(detail.quotation?.tipe_pembayaran, detail.quotation?.repayment_hari)
  const poPayment = normalizePoPayment(detail.poc.tipe_bayar, detail.poc.termin_hari)
  const approvedTop = detail.credit?.approved_top != null ? Number(detail.credit.approved_top) : null
  const paymentDiffers = paymentsDiffer(quotationPayment, poPayment)
  const poDays = poPayment?.kind === 'CREDIT' ? poPayment.days : null

  const differences: Difference[] = []

  if (paymentDiffers && quotationPayment && poPayment) {
    differences.push({
      key: 'payment',
      topic: 'Pembayaran',
      parts: [
        { text: 'Quotation ' },
        { text: formatPayment(quotationPayment), strong: true },
        { text: ', PO Customer ' },
        { text: formatPayment(poPayment), strong: true },
        { text: '.' },
      ],
    })
  }

  if (poDays !== null && approvedTop !== null && poDays > approvedTop) {
    differences.push({
      key: 'top',
      topic: 'TOP',
      parts: [
        { text: 'TOP PO ' },
        { text: `${poDays} hari`, strong: true },
        { text: ' melebihi TOP hasil verifikasi kredit ' },
        { text: `${approvedTop} hari`, strong: true },
        { text: '.' },
      ],
    })
  }

  rows
    .filter(row => row.state === 'partial' && row.po && row.quotation)
    .forEach(row => {
      differences.push({
        key: `volume-${row.key}`,
        topic: 'Volume',
        parts: [
          { text: `${row.name} diambil ` },
          { text: `${formatNumber(row.po!.volume)} dari ${formatNumber(row.quotation!.quantity)} ${VOLUME_UNIT}`, strong: true },
          { text: '.' },
        ],
      })
    })

  const untaken = rows.filter(row => row.state === 'untaken')
  if (untaken.length) {
    differences.push({
      key: 'untaken',
      topic: 'Produk',
      parts: [{ text: `${untaken.length} produk Quotation tidak diambil: ${untaken.map(row => row.name).join(', ')}.` }],
    })
  }

  const checked = [
    ...(quotationPayment && poPayment ? ['pembayaran'] : []),
    ...(poDays !== null && approvedTop !== null ? ['TOP'] : []),
    'produk',
    'volume',
  ]

  return { rows, differences, checked, paymentDiffers, quotationPayment, poPayment }
}

export function quotationTotals(quotation: SalesConfirmationQuotation | undefined): ScTotals {
  const dpp = Number(quotation?.dpp ?? 0)
  const total = Number(quotation?.total_harga_ppn ?? dpp)

  return {
    volume: Number(quotation?.total_volume ?? 0),
    material: Number(quotation?.subtotal_material ?? 0),
    transport: Number(quotation?.transport_total ?? 0),
    dpp,
    ppnRate: Number(quotation?.ppn_rate ?? 0),
    ppn: Math.round((total - dpp) * 100) / 100,
    total,
  }
}

export function poTotals(totals: PoCustomerTotals): ScTotals {
  return {
    volume: totals.volume,
    material: totals.material_subtotal,
    transport: totals.transport_total,
    dpp: totals.dpp,
    ppnRate: totals.ppn_rate,
    ppn: totals.ppn,
    total: totals.grand_total,
  }
}
