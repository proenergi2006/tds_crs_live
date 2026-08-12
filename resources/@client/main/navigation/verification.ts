import type { NavItem } from "./types";

export const verificationNavigation: NavItem[] = [
  {
    icon: "CheckCircle",
    title: "Verifikasi",
    badgeKey: "Verifikasi",
    subMenu: [
      {
        icon: "CheckSquare",
        pageName: "po-verification-list",
        title: "PO Supplier",
        permission: "verification.po-supplier",
        badgeKey: "vendor_po",
      },
      {
        icon: "ShoppingBag",
        pageName: "produk-hargas",
        title: "Periode Harga",
        activePageNames: [
          "produk-hargas-create",
          "produk-hargas-edit",
          "produk-hargas-detail",
        ],
        permission: "harga-produk.view",
        // item yang sama juga ada di Master Data > Harga buat role lain, ini khusus CEO
        roles: [2],
        badgeKey: "price_period",
      },
      {
        icon: "Landmark",
        pageName: "review-data-customer-admin",
        title: "Customer",
        permission: "verification.customer",
        activePageNames: ["review-data-customer-admin-detail"],
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi",
        title: "Quotation",
        permission: "verification.quotation",
        activePageNames: ["penawarans-verifikasi-detail"],
        badgeKey: "penawaran",
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi-proenergi",
        title: "Quotation Proenergi",
        permission: "penawaran.proenergi.verify",
        activePageNames: [
          "penawarans-verifikasi-detail-proenergi",
          "penawarans-verifikasi-om-detail-proenergi",
        ],
      },
    ],
  },
];
