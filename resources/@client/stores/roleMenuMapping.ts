/**
 * Role-to-Menu Mapping
 * Layer PERTAMA dari dua-layer role filtering di sidebar:
 *   Layer 1 (file ini)  — canAccessMenu()        : broad allow per grup (group title level)
 *   Layer 2 (router)    — filterMenuItemByRole()  : fine-grained per item via meta.roles
 *
 * Alur: canAccessMenu() dijalankan dulu di stores/menu.ts sebelum filterMenuItemByRole().
 * Efeknya: kalau satu role tidak punya grup di mapping ini, SELURUH grup tersembunyi
 * tanpa perlu cek item satu per satu. Kalau punya, baru filterMenuItemByRole() menyaring
 * item individual berdasarkan meta.roles di router/index.ts.
 */

/** Grup yang tampil untuk SEMUA role tanpa perlu ada di ROLE_MENU_MAPPING */
export const UNIVERSAL_MENUS: string[] = ["Dashboard"];

export const ROLE_MENU_MAPPING: Record<number, string[]> = {
  // Administrator
  // Layer-2 note: di dalam "Tata Kelola", role 1 hanya melihat Access Control
  //   (role-overview, users) dan Monitoring — "Review Data Customer" disembunyikan
  //   oleh meta.roles [9] di router.
  1: ["Tata Kelola"],

  // CEO
  // Layer-2 note: "Verifikasi" → CEO melihat PO Supplier [2,3] dan Penawaran OM [2,3,10];
  //   item BM/Proenergi tersembunyi. "Master Data" → CEO read-only:
  //   vendors-create/edit [5] dan produk-hargas-create [5] tersembunyi untuk role ini.
  2: ["Verifikasi", "Master Data"],

  // CFO
  // Layer-2 note: "Verifikasi" → CFO melihat PO Supplier [2,3] dan Penawaran OM [2,3,10].
  3: ["Verifikasi"],

  // Key Account
  // Layer-2 note: "Customer & Penawaran" → role 4 melihat Customer [4,12] dan
  //   Penawaran TDS [4,12]; item Proenergi [13,14] dan Sales Confirmation [9] tersembunyi.
  4: ["Customer & Penawaran"],

  // Procurement
  // Layer-2 note: "Master Data" → role 5 melihat semua item termasuk create/edit vendor
  //   dan harga; "Procurement" dan "Inventory" terbuka penuh untuk role ini.
  5: ["Procurement", "Inventory", "Master Data"],

  // Logistik
  // Layer-2 note: "Logistik" → role 6 hanya melihat Verifikasi LCR [6];
  //   Delivery Plan [7], Master Logistik [7], Review Data Customer [7] tersembunyi.
  6: ["Logistik"],

  // Logistik HO
  // Layer-2 note: "Logistik" → role 7 melihat Delivery Plan [7], seluruh Master
  //   Logistik [7], dan Review Data Customer [7]; Verifikasi LCR [6] tersembunyi.
  7: ["Logistik"],

  // BM (Branch Manager)
  // Layer-2 note: "Verifikasi" → BM hanya melihat Penawaran BM [8]; PO Supplier [2,3]
  //   dan Penawaran OM [2,3,10] tersembunyi. "Master Data" → BM melihat Harga
  //   [2,5,8] dan Wilayah [1,2,8]; Produk [2,5] dan Vendor/Terminal [2,5] tersembunyi.
  8: ["Verifikasi", "Master Data"],

  // Admin Finance
  // Layer-2 note: "Customer & Penawaran" → role 9 hanya melihat Sales Confirmation [9];
  //   Customer dan Penawaran [4,12] tersembunyi. "Tata Kelola" → hanya melihat
  //   Review Data Customer Admin [9]; Access Control [1] dan Monitoring [1] tersembunyi.
  9: ["Customer & Penawaran", "Tata Kelola"],

  // OM (Operations Manager)
  // Layer-2 note: "Verifikasi" → OM melihat Penawaran OM [2,3,10];
  //   PO Supplier [2,3] dan item BM/Proenergi tersembunyi.
  10: ["Verifikasi"],

  // Marketing
  // Layer-2 note: "Customer & Penawaran" → role 12 melihat Customer [4,12] dan
  //   Penawaran TDS [4,12]; item Proenergi [13,14] dan Sales Confirmation [9] tersembunyi.
  12: ["Customer & Penawaran"],

  // Marketing Proenergi
  // Layer-2 note: "Customer & Penawaran" → role 13 melihat Customer Proenergi [13,14]
  //   dan Penawaran Proenergi [13,14]; item TDS [4,12] tersembunyi.
  13: ["Customer & Penawaran"],

  // KAE Proenergi
  // Layer-2 note: sama seperti Marketing Proenergi (role 13).
  14: ["Customer & Penawaran"],

  // BM Proenergi
  // Layer-2 note: "Verifikasi" → role 15 hanya melihat Penawaran Proenergi BM [15].
  15: ["Verifikasi"],

  // OM Proenergi
  // Layer-2 note: "Verifikasi" → role 16 hanya melihat Penawaran Proenergi OM [16].
  16: ["Verifikasi"],
};

/**
 * Cek apakah role tertentu dapat mengakses grup menu tertentu (layer pertama).
 * Dipanggil di stores/menu.ts sebelum filterMenuItemByRole() untuk setiap
 * top-level item di side-menu.ts.
 */
export const canAccessMenu = (
  roleId: number | undefined,
  menuTitle: string,
): boolean => {
  if (!roleId) return false;

  if (UNIVERSAL_MENUS.includes(menuTitle)) return true;

  const allowedMenus = ROLE_MENU_MAPPING[roleId] || [];
  return allowedMenus.includes(menuTitle);
};
