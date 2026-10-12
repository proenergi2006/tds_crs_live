import type { NavSection } from "@/stores/menu";

export const verificationSection: NavSection = {
  key: "verification",
  title: "Verification",
  icon: "BadgeCheck",
  badgeKey: "Verifikasi",
  items: [
    {
      icon: "PackagePlus",
      pageName: "po-verification-list",
      title: "PO Supplier",
      permission: "verification.po-supplier",
      badgeKey: "vendor_po",
    },
    {
      icon: "Tags",
      pageName: "product-prices",
      title: "Price Period",
      activePageNames: [
        "product-prices-create",
        "product-prices-edit",
      ],
      permission: "price-period.view",
      roles: [2],
      badgeKey: "price_period",
    },
    {
      icon: "Contact",
      pageName: "review-data-customer-admin",
      title: "Customer",
      permission: "verification.customer",
      activePageNames: ["review-data-customer-admin-detail"],
    },
    {
      icon: "FileText",
      pageName: "penawarans-verifikasi",
      title: "Quotation",
      permission: ["penawaran.verify", "penawaran.verify-ceo"],
      activePageNames: [
        "penawarans-verifikasi-bm-detail",
        "penawarans-verifikasi-om-detail",
        "penawarans-verifikasi-ceo-detail",
      ],
      badgeKey: "penawaran",
    },
    {
      icon: "FileSignature",
      pageName: "penawarans-verifikasi-proenergi",
      title: "Quotation Proenergi",
      permission: ["penawaran.proenergi.verify", "penawaran.verify-ceo"],
      activePageNames: [
        "penawarans-verifikasi-bm-detail-proenergi",
        "penawarans-verifikasi-om-detail-proenergi",
        "penawarans-verifikasi-ceo-detail-proenergi",
      ],
    },
  ],
};
