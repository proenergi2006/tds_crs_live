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
        pageName: "product-prices",
        title: "Periode Harga",
        activePageNames: [
          "product-prices-create",
          "product-prices-edit",
        ],
        permission: "price-period.view",
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
        permission: "penawaran.verify",
        activePageNames: ["penawarans-verifikasi-bm-detail"],
        badgeKey: "penawaran",
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi-proenergi",
        title: "Quotation Proenergi",
        permission: "penawaran.proenergi.verify",
        activePageNames: [
          "penawarans-verifikasi-bm-detail-proenergi",
          "penawarans-verifikasi-om-detail-proenergi",
        ],
      },
    ],
  },
];
