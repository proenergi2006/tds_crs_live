export function salesConfirmationBadgeClass(
  value: number | string | null | undefined,
): string {
  switch (Number(value)) {
    case 1:
      return "bg-amber-100 text-amber-700";
    case 2:
      return "bg-blue-100 text-blue-700";
    case 4:
      return "bg-emerald-100 text-emerald-700";
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
