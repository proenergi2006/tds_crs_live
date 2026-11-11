import { formatCurrency, formatNumber } from '@/utils/format'

export const VOLUME_UNIT = 'm³'

export function formatVolume(volume: number | string | null | undefined): string {
  const rounded = Math.round((Number(volume ?? 0) + Number.EPSILON) * 100) / 100
  return `${formatNumber(rounded)} ${VOLUME_UNIT}`
}

const SHORT_UNITS = [
  { divisor: 1_000_000_000_000, label: 'triliun' },
  { divisor: 1_000_000_000, label: 'miliar' },
  { divisor: 1_000_000, label: 'juta' },
] as const

export function formatCurrencyShort(value: number): string {
  const abs = Math.abs(value)
  const unit = SHORT_UNITS.find(candidate => abs >= candidate.divisor)
  if (!unit) return formatCurrency(value)

  const scaled = (abs / unit.divisor).toLocaleString('id-ID', { maximumFractionDigits: 1 })
  return `${value < 0 ? '-' : ''}Rp ${scaled} ${unit.label}`
}
