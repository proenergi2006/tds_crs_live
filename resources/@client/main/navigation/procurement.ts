import type { NavSection } from "@/stores/menu";

export const procurementSection: NavSection = {
  key: "procurement",
  title: "Procurement",
  icon: "Receipt",
  items: [
    {
      icon: "PackagePlus",
      pageName: "vendor-pos-list",
      title: "PO Supplier",
      activePageNames: [
        "vendor-pos-create",
        "vendor-pos-edit",
        "vendor-pos-detail",
        "vendor-pos-receive",
      ],
      permission: "po-supplier.manage",
    },
    {
      icon: "PackageCheck",
      pageName: "good-receipt-list",
      title: "Goods Receipt",
      permission: "good-receipt.manage",
    },
  ],
};
