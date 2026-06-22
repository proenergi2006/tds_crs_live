/* Format tanggal & waktu dengan locale id-ID, dipakai lintas halaman SystemDesign. */

/** Tanggal panjang, mis. "22 Juni 2026". Mengembalikan "-" jika kosong. */
export function formatDate(value?: string | null): string {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : '-'
}

/**
 * Tanggal + jam, mis. "22 Jun 2026, 14:30".
 * Mengembalikan `undefined` (bukan "-") jika kosong, agar pemanggil bisa
 * menyembunyikan elemen ketika datanya belum ada.
 */
export function formatDateTime(value?: string | null): string | undefined {
  if (!value) return undefined
  return new Date(value).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}
