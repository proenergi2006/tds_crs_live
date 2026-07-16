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
      },
      {
        icon: "Users",
        pageName: "customers-verification-list",
        title: "Customer",
        permission: "verification.customer",
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi",
        title: "Quotation",
        permission: "verification.quotation",
        activePageNames: [
          "penawarans-verifikasi-detail"
        ]
      },
      // {
      //   icon: 'File',
      //   pageName: 'penawarans-verifikasi-proenergi',
      //   title: 'Quotation (Proenergi BM)',
      //   permission: 'penawaran.proenergi.verify-bm',
      // },
      // {
      //   icon: 'File',
      //   pageName: 'penawarans-verifikasi-om-proenergi',
      //   title: 'Quotation (Proenergi OM)',
      //   permission: 'penawaran.proenergi.verify-om',
      // },
      {
        icon: "ClipboardCheck",
        pageName: "sales-confirmations-bm",
        title: "Sales Confirmation (BM)",
        activePageNames: [
          "sales-confirmations-bm-detail",
          "sales-confirmations-bm-detail-po",
        ],
        permission: "sales-confirmation.manage",
      },
    ],
  },
];
