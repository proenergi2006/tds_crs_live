export function poCustomerStatusBadgeClass(
  key: string | null | undefined,
): string {
  switch (key) {
    case "awaiting_process":
      return "bg-slate-100 text-slate-700";
    case "blocked":
      return "bg-rose-100 text-rose-700";
    case "sc_in_progress":
      return "bg-amber-100 text-amber-700";
    case "done":
      return "bg-emerald-100 text-emerald-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}
