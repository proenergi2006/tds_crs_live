/**
 * Role-to-Menu Mapping
 * Centralized configuration untuk menentukan menu apa yang dapat diakses oleh role tertentu
 */

/**
 * Menu yang tampil untuk SEMUA role (universal menus)
 */
export const UNIVERSAL_MENUS: string[] = ["Profile", "Home"];

export const ROLE_MENU_MAPPING: Record<number, string[]> = {
    // Administrator
    1: ["Access Control"],

    // CEO
    2: ["Referensi Data", "Verifikasi"],

    // CFO
    3: ["Verifikasi"],

    // Key Account
    4: ["Customer", "PO Customer"],

    // Procurement
    5: [
        "Transactions Data",
        "Inventory Data",
        "List Pengiriman",
        "Verifikasi Pengiriman",
        "Request",
        "Report",
        "Referensi Data",
    ],

    // Logistik
    6: ["Verifikasi LCR - Logistik"],

    // Logistik HO
    7: ["Master Logistik", "Review Data Customer Logistik", "Delivery Plan"],

    // BM (Branch Manager)
    8: [
        "Sales Confirmation (BM)",
        "Verifikasi BM",
        "Review Customer BM",
        "Master Wilayah",
        "Harga Bm",
    ],

    // Admin Finance
    9: ["Sales Confirmation", "Review Data Customer Admin"],

    // OM (Operations Manager)
    10: ["Verifikasi-om", "Review Data Customer OM"],

    // Marketing Proenergi
    13: ["Customer "],

    // KAE Proenergi
    14: ["Customer "],

    // BM Proenergi
    15: ["Verifikasi "],

    // OM Proenergi
    16: ["Verifikasi Penawaran"],
};

/**
 * Utility function untuk check apakah role tertentu dapat akses menu tertentu
 * @param roleId - ID dari role user
 * @param menuTitle - Judul menu yang ingin diakses
 * @returns true jika role dapat akses menu, false sebaliknya
 */
export const canAccessMenu = (
    roleId: number | undefined,
    menuTitle: string,
): boolean => {
    if (!roleId) return false;

    // Menu universal tampil untuk semua role
    if (UNIVERSAL_MENUS.includes(menuTitle)) return true;

    // Menu role-specific check
    const allowedMenus = ROLE_MENU_MAPPING[roleId] || [];
    return allowedMenus.includes(menuTitle);
};
