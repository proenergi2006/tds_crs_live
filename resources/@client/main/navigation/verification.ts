import type { NavSection } from "@/stores/menu";
import { quotationPermissionsForAllBrands } from "@/utils/quotation-brand";

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
      pageName: "verification.quotations.index",
      title: "Quotation",
      permission: quotationPermissionsForAllBrands("verify"),
      activePageNames: ["verification.quotations.show"],
      badgeKey: "penawaran",
    },
    {
      icon: "ClipboardCheck",
      pageName: "sales-confirmations",
      title: "Sales Confirmation",
      activePageNames: [
        "sales-confirmations-detail",
        "sales-confirmations-delivery-queue",
        "po-customer-plan",
      ],
      permission: [
        "sales-confirmation.manage",
        "sales-confirmation.view",
        "sales-confirmation.approve",
      ],
    },
  ],
};
