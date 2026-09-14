export type VerifikasiRole = "bm" | "om";
export type VerifikasiBrand = "tds" | "proenergi";

export interface VerifikasiConfig {
  title: string;
  description: string;
  endpoint: string;
  detailRouteName: string;
}

export interface VerifikasiDetailConfig {
  title: string;
  fetchEndpoint: string;
  verifyEndpoint: (id: number) => string;
  rejectEndpoint: (id: number) => string;
  actionWaitingStatus: string;
  hargaPriceListField: "harga_price_list" | "harga_price_list_pe";
  showMarginSection: boolean;
  showCogsRow: boolean;
}

const ENDPOINT_BASE: Record<VerifikasiBrand, string> = {
  tds: "/penawarans",
  proenergi: "/penawarans-proenergi",
};

const ROLE_LABEL: Record<VerifikasiRole, string> = {
  bm: "Branch Manager",
  om: "Operational Manager",
};

export function getVerifikasiConfig(
  role: VerifikasiRole,
  brand: VerifikasiBrand,
): VerifikasiConfig {
  const isProenergi = brand === "proenergi";
  const isOm = role === "om";

  return {
    title: isProenergi
      ? "Daftar Verifikasi Penawaran Proenergi"
      : "Daftar Verifikasi Penawaran",
    description: `Monitor penawaran yang menunggu verifikasi ${ROLE_LABEL[role]}.`,
    endpoint: `${ENDPOINT_BASE[brand]}/${role}`,
    detailRouteName: `penawarans-verifikasi-${isOm ? "om" : "bm"}-detail${isProenergi ? "-proenergi" : ""}`,
  };
}

export function getVerifikasiDetailConfig(
  role: VerifikasiRole,
  brand: VerifikasiBrand,
): VerifikasiDetailConfig {
  const isProenergi = brand === "proenergi";
  const isOm = role === "om";
  const base = ENDPOINT_BASE[brand];

  return {
    title: `Verifikasi Penawaran ${role.toUpperCase()}${isProenergi ? " Proenergi" : ""}`,
    fetchEndpoint: base,
    verifyEndpoint: (id) => `${base}/${id}/verifikasi${isOm ? "-om" : ""}`,
    rejectEndpoint: (id) => `${base}/${id}/tolak-${role}`,
    actionWaitingStatus: isOm ? "approved_bm" : "waiting_branch_manager",
    hargaPriceListField: isProenergi
      ? "harga_price_list_pe"
      : "harga_price_list",
    showMarginSection: isOm,
    showCogsRow: isOm,
  };
}
