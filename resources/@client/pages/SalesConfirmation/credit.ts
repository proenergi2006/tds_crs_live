import type { ArBucketKey, SalesConfirmationDetail } from '@/types'

export const AR_BUCKETS: readonly { key: ArBucketKey; label: string }[] = [
  { key: 'outstanding_current', label: 'Belum jatuh tempo' },
  { key: 'overdue_1_30', label: '1–30 hari' },
  { key: 'overdue_31_60', label: '31–60 hari' },
  { key: 'overdue_61_90', label: '61–90 hari' },
  { key: 'overdue_90_plus', label: '> 90 hari' },
]

export function resolveArBuckets(detail: SalesConfirmationDetail): Record<ArBucketKey, number> {
  const arnya = detail.arnya ?? {}
  const header = detail.sc_header

  return {
    outstanding_current: Number(header?.not_yet ?? arnya.outstanding_current ?? 0),
    overdue_1_30: Number(header?.ov_under_30 ?? arnya.overdue_1_30 ?? 0),
    overdue_31_60: Number(header?.ov_under_60 ?? arnya.overdue_31_60 ?? 0),
    overdue_61_90: Number(header?.ov_under_90 ?? arnya.overdue_61_90 ?? 0),
    overdue_90_plus: Number(header?.ov_up_90 ?? arnya.overdue_90_plus ?? 0),
  }
}
