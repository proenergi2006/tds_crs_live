export function poCustomerStatusBadgeClass(
  key: string | null | undefined,
): string {
  switch (key) {
    case "sc_in_progress":
      return "bg-amber-100 text-amber-700";
    case "done":
      return "bg-emerald-100 text-emerald-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}
