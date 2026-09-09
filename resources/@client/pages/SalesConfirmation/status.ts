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
