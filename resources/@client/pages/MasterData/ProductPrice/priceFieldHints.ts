export const PRICE_FIELD_HINTS = {
  cogs_basis: "Loco: basis harga material-only. Franco: basis harga transport included dari quarry.",
  cogs_material_price: "Harga beli material dari quarry.",
  margin_material_amount: "Keuntungan dari penjualan material.",
  price_list: "Menjadi Harga Dasar untuk Penawaran.",
  tier_limits: "Batas Harga Dasar terendah untuk approval level tersebut.",
  cogs_transport_price: "Biaya angkut yang di charge oleh quarry.",
  margin_transport_amount: "Keuntungan transport dari Logistik untuk produk ini.",
  transport_price: "Harga transport terkunci di Penawaran.",
} as const;

export function francoTransportPrice(cogsTransport: unknown, marginTransport: unknown): number | null {
  if (cogsTransport === null || cogsTransport === undefined || cogsTransport === "") return null;
  if (marginTransport === null || marginTransport === undefined || marginTransport === "") return null;

  return Number(cogsTransport) + Number(marginTransport);
}
