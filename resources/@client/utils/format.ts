/** "YYYY-MM-DD", tanpa jam. Kosong → "". */
export function toDateKey(value?: string | null): string {
  return value ? String(value).slice(0, 10) : "";
}

/** Hari ini sebagai "YYYY-MM-DD". */
export function todayDateKey(): string {
  const d = new Date();
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/** "dd/mm/YYYY – dd/mm/YYYY". Sisi kosong → "-". */
export function formatDateRangeShort(from?: string | null, to?: string | null): string {
  const fmt = (v?: string | null) => {
    const k = toDateKey(v);
    return k ? `${k.slice(8, 10)}/${k.slice(5, 7)}/${k.slice(0, 4)}` : "-";
  };
  return `${fmt(from)} – ${fmt(to)}`;
}

/** Contoh: "22 Juni 2026". Kosong → "-". */
export function formatDate(value?: string | null): string {
  return value
    ? new Date(value).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
      })
    : "-";
}

/** Contoh: "22 Jun 2026, 14.30". Kosong → undefined (bukan "-"), supaya elemen bisa disembunyikan. */
export function formatDateTime(value?: string | null): string | undefined {
  if (!value) return undefined;
  return new Date(value).toLocaleString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

/** Contoh: "Rp 1.500.000". Kosong → "-". */
export function formatCurrency(value?: number | string | null): string {
  if (value === null || value === undefined || value === "") return "-";
  const n = typeof value === "string" ? parseFloat(value) : value;
  return isNaN(n) ? "-" : "Rp " + n.toLocaleString("id-ID");
}

export function formatNumber(v: number | string = 0) {
  const n = typeof v === "string" ? parseFloat(v) : (v ?? 0);
  return !isNaN(n) ? n.toLocaleString("id-ID") : "-";
}

/** Tarif PPN default untuk dokumen baru. */
export const DEFAULT_PPN_RATE = 0.11;

/** 0.11 → "11%", 0.115 → "11,5%". Kosong → "-". */
export function formatPercentRate(rate?: number | string | null): string {
  const n = Number(rate);
  if (rate === null || rate === undefined || rate === "" || isNaN(n)) return "-";
  return `${(Math.round(n * 10000) / 100).toLocaleString("id-ID")}%`;
}
