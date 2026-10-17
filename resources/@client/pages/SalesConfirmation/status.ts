import type { SalesConfirmationStatus } from "@/types";

export const SALES_CONFIRMATION_STATUS = {
  PendingAdmin: 1,
  PendingBm: 2,
  PendingOm: 3,
  Confirmed: 4,
  Rejected: 5,
} as const;

export const SALES_CONFIRMATION_STATUS_OPTIONS: readonly {
  value: SalesConfirmationStatus | "";
  label: string;
}[] = [
  { value: "", label: "Semua status" },
  { value: SALES_CONFIRMATION_STATUS.PendingAdmin, label: "Menunggu Verifikasi Admin" },
  { value: SALES_CONFIRMATION_STATUS.PendingBm, label: "Menunggu Verifikasi BM" },
  { value: SALES_CONFIRMATION_STATUS.PendingOm, label: "Menunggu Verifikasi OM" },
  { value: SALES_CONFIRMATION_STATUS.Confirmed, label: "Confirmed" },
  { value: SALES_CONFIRMATION_STATUS.Rejected, label: "Ditolak" },
];

export function salesConfirmationBadgeClass(
  value: number | string | null | undefined,
): string {
  switch (Number(value)) {
    case SALES_CONFIRMATION_STATUS.PendingAdmin:
      return "bg-amber-100 text-amber-700";
    case SALES_CONFIRMATION_STATUS.PendingBm:
    case SALES_CONFIRMATION_STATUS.PendingOm:
      return "bg-blue-100 text-blue-700";
    case SALES_CONFIRMATION_STATUS.Confirmed:
      return "bg-emerald-100 text-emerald-700";
    case SALES_CONFIRMATION_STATUS.Rejected:
      return "bg-rose-100 text-rose-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}

export const UNBLOCK_ROLE_ADMIN_FINANCE = 9;

export function unblockStatusBadgeClass(status?: string | null): string {
  if (status === "in_progress") return "bg-amber-100 text-amber-700";
  if (status === "approved") return "bg-emerald-100 text-emerald-700";
  if (status === "rejected") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-700";
}

export function unblockStepBadgeClass(status?: string | null): string {
  if (status === "pending") return "bg-amber-100 text-amber-700";
  if (status === "approved") return "bg-emerald-100 text-emerald-700";
  if (status === "rejected") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-700";
}

export function unblockStepStatusLabel(status?: string | null): string {
  if (status === "pending") return "Menunggu";
  if (status === "approved") return "Disetujui";
  if (status === "rejected") return "Ditolak";
  return status ?? "-";
}
