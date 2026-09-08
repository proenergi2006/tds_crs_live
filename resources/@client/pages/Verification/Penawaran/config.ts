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

export function getDisposisiLabel(
  value: string | number | null | undefined,
): string {
  switch (String(value)) {
    case "1":
      return "Draft";
    case "2":
      return "Menunggu Verifikasi BM";
    case "3":
      return "Menunggu Verifikasi OM";
    case "4":
      return "Disetujui OM";
    case "5":
      return "Ditolak BM";
    case "6":
      return "Ditolak OM";
    default:
      return "-";
  }
}

export function disposisiBadgeClass(
  value: string | number | null | undefined,
): string {
  switch (String(value)) {
    case "1":
      return "bg-slate-100 text-slate-700";
    case "2":
      return "bg-amber-100 text-amber-700";
    case "3":
      return "bg-blue-100 text-blue-700";
    case "4":
      return "bg-emerald-100 text-emerald-700";
    case "5":
    case "6":
      return "bg-rose-100 text-rose-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}
