export const PRICE_FIELD_HINTS = {
  cogs_basis:
    'Loco: material diambil di quarry, transport diatur TDS. Franco: quarry mengantar sendiri, biaya angkut sudah termasuk di harga beli.',
  cogs_material_price: 'Harga beli material dari quarry, tanpa biaya angkut.',
  margin_material_amount: 'Keuntungan dari penjualan material.',
  price_list: 'Menjadi Harga Dasar di Penawaran. Tidak termasuk harga transport.',
  tier_limits: 'Harga Dasar terendah yang masih bisa disetujui level tersebut.',
  cogs_transport_price: 'Biaya angkut yang dibayar ke quarry, sudah termasuk di harga beli franco.',
  margin_transport_amount: 'Keuntungan transport yang ditargetkan Logistik untuk produk ini.',
  transport_price: 'COGS transport + margin transport. Menjadi harga transport terkunci di Penawaran.',
} as const

export function francoTransportPrice(cogsTransport: unknown, marginTransport: unknown): number | null {
  if (cogsTransport === null || cogsTransport === undefined || cogsTransport === '') return null
  if (marginTransport === null || marginTransport === undefined || marginTransport === '') return null

  return Number(cogsTransport) + Number(marginTransport)
}
