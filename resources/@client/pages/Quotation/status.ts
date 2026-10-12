import type { QuotationStatus } from '@/types'

export const QUOTATION_STATUS = {
  Draft: 'draft',
  WaitingVerification: 'waiting_verification',
  Approved: 'approved',
  Rejected: 'rejected',
} as const satisfies Record<string, QuotationStatus>

export function quotationStatusLabel(status: string | null | undefined): string {
  switch (status) {
    case QUOTATION_STATUS.Draft:
      return 'Draft'
    case QUOTATION_STATUS.WaitingVerification:
      return 'Menunggu Verifikasi'
    case QUOTATION_STATUS.Approved:
      return 'Disetujui'
    case QUOTATION_STATUS.Rejected:
      return 'Ditolak'
    default:
      return '-'
  }
}

export function quotationStatusBadgeClass(status: string | null | undefined): string {
  switch (status) {
    case QUOTATION_STATUS.Draft:
      return 'bg-slate-100 text-slate-700'
    case QUOTATION_STATUS.WaitingVerification:
      return 'bg-amber-100 text-amber-700'
    case QUOTATION_STATUS.Approved:
      return 'bg-emerald-100 text-emerald-700'
    case QUOTATION_STATUS.Rejected:
      return 'bg-rose-100 text-rose-700'
    default:
      return 'bg-slate-100 text-slate-500'
  }
}

export function priceTierLabel(tier: string | null | undefined): string {
  switch (tier) {
    case 'tier_1':
      return 'Tier I'
    case 'tier_2':
      return 'Tier II'
    case 'tier_3':
      return 'Tier III'
    case 'out_of_range':
      return 'Di luar batas tier'
    default:
      return '-'
  }
}

export function priceTierBadgeClass(tier: string | null | undefined): string {
  switch (tier) {
    case 'tier_1':
      return 'bg-emerald-100 text-emerald-700'
    case 'tier_2':
      return 'bg-blue-100 text-blue-700'
    case 'tier_3':
      return 'bg-amber-100 text-amber-700'
    case 'out_of_range':
      return 'bg-red-100 text-red-700'
    default:
      return 'bg-slate-100 text-slate-500'
  }
}

export function transportLegLabel(type: string | null | undefined): string {
  switch (type) {
    case 'VESSEL':
      return 'Kapal'
    case 'TRUCK':
      return 'Truck'
    default:
      return '-'
  }
}

export function transportBasisLabel(basis: string | null | undefined): string {
  switch (basis) {
    case 'loco':
      return 'Loco'
    case 'franco':
      return 'Franco'
    default:
      return '-'
  }
}
