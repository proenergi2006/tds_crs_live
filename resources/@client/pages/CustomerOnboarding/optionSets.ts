/**
 * Opsi enum/radio hardcode dipakai lintas step component + Step 5 (Summary,
 * untuk resolve label dari value tersimpan) — satu sumber supaya tidak ada
 * drift antara step pengisian dan step recap. Bukan fetch API (Blueprint §6).
 */

export const typeBusinessOptions = [
  'Agriculture & Forestry / Horticulture', 'Business & Information',
  'Construction / Utilities / Contracting', 'Education', 'Finance & Insurance',
  'Food & hospitality', 'Gaming', 'Health Services', 'Motor Vehicle',
]

export const ownershipOptions = [
  'Affiliation', 'National Private', 'Foreign Private', 'Joint Venture',
  'BUMN / BUMD', 'Foundation', 'Personal',
]

export const incoTermsOptions = [
  { code: 'EXW', label: 'Ex Works' },
  { code: 'FOB', label: 'Free On Board' },
  { code: 'CIF', label: 'Cost, Insurance and Freight' },
  { code: 'CFR', label: 'Cost and Freight' },
  { code: 'DDP', label: 'Delivered Duty Paid' },
  { code: 'DAP', label: 'Delivered At Place' },
  { code: 'FCA', label: 'Free Carrier' },
  { code: 'CPT', label: 'Carriage Paid To' },
]

export const pricingMethodOptions = ['Discount Pricelist', 'Quotation']

export const paymentMethodOptions = ['SKBDN', 'Bank Guarantee', 'Cover Cek-Giro', 'Transfer']

export const paymentTermOptions = [
  { code: 'CBD', label: 'Cash Before Delivery' },
  { code: 'COD', label: 'Cash on Delivery' },
  { code: 'CREDIT', label: 'Credit' },
]

export const paymentTermBasisOptions = [
  { code: 'days_after_delivery', label: 'Days After Delivery' },
  { code: 'days_after_invoice_received', label: 'Days After Invoice Received' },
]

export const siteEnvironmentOptions = [
  { value: 'industrial', label: 'Industri' },
  { value: 'residential', label: 'Pemukiman' },
]

export const storageTypeOptions = [
  { value: 'indoor', label: 'Indoor' },
  { value: 'outdoor', label: 'Outdoor' },
]

export const operatingHoursOptions = [
  { value: 'standard_office_hours', label: '08.00 - 17.00' },
  { value: 'twenty_four_hours', label: '24 Hours' },
]

export const qualityCheckingOptions = [
  { value: 'lab_test', label: 'Lab Test' },
]

export const quantityCheckingOptions = [
  { value: 'weighbridge_truck_scale', label: 'Weighbridge (Truck Scale)' },
  { value: 'platform_scale', label: 'Platform Scale' },
  { value: 'volume_measurement', label: 'Volume Measurement' },
  { value: 'truck_counting', label: 'Truck Counting' },
  { value: 'delivery_order_verification', label: 'Delivery Order Verification' },
  { value: 'net_weight_verification', label: 'Net Weight Verification' },
  { value: 'sampling', label: 'Sampling' },
]

/** Cari label dari daftar { code|value, label } berdasarkan value tersimpan. */
export function resolveOptionLabel(
  value: string,
  options: Array<{ code?: string; value?: string; label: string }>,
): string {
  const found = options.find((opt) => (opt.code ?? opt.value) === value)
  return found?.label ?? value
}
