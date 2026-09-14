export function disposisiBadgeClass(value: string | number | null | undefined): string {
  switch (String(value)) {
    case '1':
      return 'bg-slate-100 text-slate-700'
    case '2':
      return 'bg-amber-100 text-amber-700'
    case '3':
      return 'bg-blue-100 text-blue-700'
    case '4':
      return 'bg-emerald-100 text-emerald-700'
    case '5':
    case '6':
      return 'bg-rose-100 text-rose-700'
    default:
      return 'bg-slate-100 text-slate-500'
  }
}
