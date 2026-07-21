/* Format tanggal & waktu dengan locale id-ID, dipakai lintas halaman SystemDesign. */

/** Tanggal panjang, mis. "22 Juni 2026". Mengembalikan "-" jika kosong. */
export function formatDate(value?: string | null): string {
  return value
    ? new Date(value).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
      })
    : "-";
}

/**
 * Tanggal + jam, mis. "22 Jun 2026, 14:30".
 * Mengembalikan `undefined` (bukan "-") jika kosong, agar pemanggil bisa
 * menyembunyikan elemen ketika datanya belum ada.
 */
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

/** Nominal mata uang, mis. "Rp 1.500.000". Mengembalikan "-" jika kosong. */
export function formatCurrency(value?: number | string | null): string {
  if (value === null || value === undefined || value === "") return "-";
  const n = typeof value === "string" ? parseFloat(value) : value;
  return isNaN(n) ? "-" : "Rp " + n.toLocaleString("id-ID");
}

export function formatNumber(v: number | string = 0) {
  const n = typeof v === "string" ? parseFloat(v) : (v ?? 0);
  return !isNaN(n) ? n.toLocaleString("id-ID") : "-";
}
