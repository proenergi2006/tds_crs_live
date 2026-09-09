import type { NavItem } from "./types";

export const customerNavigation: NavItem[] = [
  {
    icon: "Users",
    title: "CRM",
    subMenu: [
      {
        icon: "User",
        pageName: "customers-list",
        title: "Customer",
        activePageNames: ["customer-detail", "customers-create"],
        permission: "customer.viewOwn",
      },
      {
        icon: "File",
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
        icon: "File",
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
      },
      {
        icon: "ClipboardCheck",
        pageName: "sales-confirmations",
        title: "Sales Confirmation",
        activePageNames: [
          "sales-confirmations-detail",
          "sales-confirmations-delivery-queue",
          "sales-confirmations-bm-detail-po",
          "po-customer-plan",
        ],
        permission: "sales-confirmation.manage",
      },
    ],
  },
];
