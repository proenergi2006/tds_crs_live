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
        activePageNames: [
          "customer-detail",
          "customers-create",
          "customers-edit",
        ],
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
        permission: "penawaran.proenergi.manage",
      },
      {
        icon: "ShoppingCart",
        pageName: "po-customers-index",
        title: "Sales Order",
        activePageNames: ["penawarans-po", "po-customer-plan"],
        permission: "penawaran.tds.manage",
      },
      {
        icon: "ClipboardCheck",
        pageName: "sales-confirmations",
        title: "Sales Confirmation",
        permission: "sales-confirmation.manage",
      },
    ],
  },
];
