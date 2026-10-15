import type { QuotationBrand } from '@/types'

export type QuotationAbility = 'manage' | 'viewAny' | 'viewOwn' | 'verify'

export type PermissionCheck = (permission: string) => boolean

export const QUOTATION_BRANDS: readonly QuotationBrand[] = ['tds', 'proenergi']

export const QUOTATION_BRAND_FILTER_OPTIONS: readonly { value: QuotationBrand | ''; label: string }[] = [
  { value: '', label: 'Semua Brand' },
  { value: 'tds', label: 'TDS' },
  { value: 'proenergi', label: 'Proenergi' },
]

const PERMISSION_PREFIX: Record<QuotationBrand, string> = {
  tds: 'quotation',
  proenergi: 'quotation.proenergi',
}

export function quotationPermission(brand: QuotationBrand, ability: QuotationAbility): string {
  return `${PERMISSION_PREFIX[brand]}.${ability}`
}

export function quotationPermissionsForAllBrands(ability: QuotationAbility): string[] {
  return QUOTATION_BRANDS.map((brand) => quotationPermission(brand, ability))
}

export function canManageQuotationBrand(can: PermissionCheck, brand: QuotationBrand): boolean {
  return can(quotationPermission(brand, 'manage'))
}

export function viewableBrands(can: PermissionCheck): QuotationBrand[] {
  return QUOTATION_BRANDS.filter(
    (brand) => can(quotationPermission(brand, 'viewAny')) || can(quotationPermission(brand, 'viewOwn')),
  )
}

export function verifiableBrands(can: PermissionCheck): QuotationBrand[] {
  return QUOTATION_BRANDS.filter((brand) => can(quotationPermission(brand, 'verify')))
}

export function manageableBrands(can: PermissionCheck): QuotationBrand[] {
  return QUOTATION_BRANDS.filter((brand) => canManageQuotationBrand(can, brand))
}

// User dua brand dipaksa TDS, BE menolak POST tanpa brand
export function defaultCreateBrand(can: PermissionCheck): QuotationBrand | null {
  const manageable = manageableBrands(can)
  if (manageable.length === 0) return null
  if (manageable.length === 1) return manageable[0]
  return 'tds'
}
