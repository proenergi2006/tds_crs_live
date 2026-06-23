import { formatDateTime } from "@/utils/format";

export type VerifikasiRole = "bm" | "om";
export type VerifikasiBrand = "reguler" | "proenergi";

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
  showOmCatatan: boolean;
  catatanVerifikasiLabel: string;
}

const ENDPOINT_BASE: Record<VerifikasiBrand, string> = {
  reguler: "/penawarans",
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
    detailRouteName: `penawarans-verifikasi${isOm ? "-om" : ""}-detail${isProenergi ? "-proenergi" : ""}`,
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
    showOmCatatan: isOm,
    catatanVerifikasiLabel: isOm
      ? "Catatan Verifikasi BM"
      : "Catatan Verifikasi",
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
      return "bg-slate-100 text-slate-700"; // draft
    case "2":
      return "bg-amber-100 text-amber-700"; // menunggu BM
    case "3":
      return "bg-blue-100 text-blue-700"; // menunggu OM
    case "4":
      return "bg-emerald-100 text-emerald-700"; // disetujui OM
    case "5":
    case "6":
      return "bg-rose-100 text-rose-700"; // ditolak
    default:
      return "bg-slate-100 text-slate-700";
  }
}

const STATUS_LABEL: Record<string, string> = {
  draft: "Draft",
  waiting_branch_manager: "Menunggu BM",
  approved_bm: "Approved BM",
  approved_om: "Approved OM",
  rejected_bm: "Rejected BM",
  rejected_om: "Rejected OM",
};

const STATUS_BADGE_CLASS: Record<string, string> = {
  draft: "bg-slate-100 text-slate-700",
  waiting_branch_manager: "bg-amber-100 text-amber-700",
  approved_bm: "bg-blue-100 text-blue-700",
  approved_om: "bg-emerald-100 text-emerald-700",
  rejected_bm: "bg-rose-100 text-rose-700",
  rejected_om: "bg-rose-100 text-rose-700",
};

export function formatStatusLabel(status?: string | null): string {
  if (!status) return "-";
  return STATUS_LABEL[status] ?? status;
}

export function statusBadgeClass(status?: string | null): string {
  if (!status) return "bg-slate-100 text-slate-700";
  return STATUS_BADGE_CLASS[status] ?? "bg-slate-100 text-slate-700";
}

export function getDisposisiTanggal(pen: {
  disposisi_penawaran?: string | number;
  bm_tanggal?: string | null;
  om_tanggal?: string | null;
}): string {
  const disposisi = String(pen.disposisi_penawaran);

  if (disposisi === "3" && pen.bm_tanggal)
    return `Approved BM: ${formatDateTime(pen.bm_tanggal)}`;
  if (disposisi === "4" && pen.om_tanggal)
    return `Approved OM: ${formatDateTime(pen.om_tanggal)}`;
  if (disposisi === "5" && pen.bm_tanggal)
    return `Rejected BM: ${formatDateTime(pen.bm_tanggal)}`;
  if (disposisi === "6" && pen.om_tanggal)
    return `Rejected OM: ${formatDateTime(pen.om_tanggal)}`;

  return "";
}
