import type { NavSection } from "@/stores/menu";

export const commercialSection: NavSection = {
  key: "commercial",
  title: "Commercial",
  icon: "Briefcase",
  items: [
    {
      icon: "Contact",
      pageName: "customers-list",
      title: "Customer",
      activePageNames: ["customer-detail", "customers-create"],
      permission: "customer.viewOwn",
    },
    {
      icon: "FileText",
      pageName: "penawarans-list",
      title: "Quotation",
      activePageNames: [
        "penawarans-create",
        "penawarans-edit",
        "penawarans-detail",
      ],
      permission: "penawaran.viewOwn",
    },
    {
      icon: "FileSignature",
      pageName: "penawarans-list-proenergi",
      title: "Quotation Proenergi",
      activePageNames: [
        "penawarans-create-proenergi",
        "penawarans-edit-proenergi",
        "penawarans-detail-proenergi",
      ],
      permission: "penawaran.proenergi.viewOwn",
    },
    {
      icon: "ShoppingCart",
      pageName: "po-customers-index",
      title: "PO Customer",
      activePageNames: ["po-customers-detail", "penawarans-po"],
      permission: ["penawaran.viewAny", "penawaran.manage"],
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
      permission: ["sales-confirmation.manage", "sales-confirmation.view"],
    },
    {
      icon: "CalendarClock",
      pageName: "ar-agings-index",
      title: "AR Aging Customer",
      activePageNames: [],
      permission: "sales-confirmation.manage",
    },
  ],
};
