import { type Menu } from "@/stores/menu";

const menu: Array<Menu | "divider"> = [
  /* Section: Dashboard */
  {
    icon: "Home",
    title: "Dashboard",
    subMenu: [
      {
        icon: "User",
        pageName: "profile-overview-1",
        title: "Profile",
      },
      {
        icon: "Activity",
        pageName: "dashboard-overview-1",
        title: "Dashboard Utama",
      },
    ],
  },

  /* Section: Customer & Penawaran */
  {
    icon: "Users",
    title: "Customer & Penawaran",
    subMenu: [
      {
        icon: "User",
        pageName: "customers-list",
        title: "Customer",
        activePageNames: ["customers-create", "customers-edit"],
      },
      {
        icon: "User",
        pageName: "customers-list-proenergi",
        title: "Customer (Proenergi)",
        activePageNames: ["customers-create-proenergi", "customers-edit-proenergi"],
      },
      {
        icon: "File",
        pageName: "penawarans-list",
        title: "Penawaran",
        activePageNames: ["penawarans-create", "penawarans-edit", "penawarans-detail"],
      },
      {
        icon: "File",
        pageName: "penawarans-list-proenergi",
        title: "Penawaran (Proenergi)",
        activePageNames: [
          "penawarans-create-proenergi",
          "penawarans-edit-proenergi",
          "penawarans-detail-proenergi",
        ],
      },
      {
        icon: "ClipboardCheck",
        pageName: "sales-confirmations",
        title: "Sales Confirmation",
      },
    ],
  },

  /* Section: Verifikasi */
  {
    icon: "CheckCircle",
    title: "Verifikasi",
    subMenu: [
      {
        icon: "CheckSquare",
        pageName: "po-verification-list",
        title: "PO Supplier",
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi",
        title: "Penawaran (BM)",
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi-om",
        title: "Penawaran (OM)",
        activePageNames: ["penawarans-verifikasi-om-detail"],
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi-proenergi",
        title: "Penawaran (Proenergi BM)",
      },
      {
        icon: "File",
        pageName: "penawarans-verifikasi-om-proenergi",
        title: "Penawaran (Proenergi OM)",
      },
    ],
  },

  /* Section: Procurement */
  {
    icon: "Receipt",
    title: "Procurement",
    subMenu: [
      {
        icon: "Inbox",
        pageName: "vendor-pos-list",
        title: "PO Supplier",
        activePageNames: [
          "vendor-pos-create",
          "vendor-pos-edit",
          "vendor-pos-detail",
          "vendor-pos-receive",
        ],
      },
      {
        icon: "FileCheck",
        pageName: "good-receipt-list",
        title: "Good Receipts",
      },
      {
        icon: "ClipboardList",
        pageName: "procurement-delivery-requests",
        title: "Delivery Request",
        activePageNames: ["procurement-dr-detail"],
      },
    ],
  },

  /* Section: Inventory */
  {
    icon: "Boxes",
    title: "Inventory",
    subMenu: [
      {
        icon: "Inbox",
        pageName: "StockInventory",
        title: "Stock Inventory",
      },
    ],
  },

  /* Section: Logistik */
  {
    icon: "Truck",
    title: "Logistik",
    subMenu: [
      {
        icon: "MapPin",
        pageName: "logistik-lcrs",
        title: "Verifikasi LCR",
      },
      {
        icon: "ClipboardList",
        pageName: "logistics-delivery-plan",
        title: "Delivery Plan",
      },
      {
        icon: "Database",
        title: "Master Logistik",
        subMenu: [
          {
            icon: "Users",
            pageName: "transportir-list",
            title: "Transportir",
          },
          {
            icon: "User",
            pageName: "personnel-list",
            title: "Personnel",
          },
          {
            icon: "Package",
            pageName: "volumes-list",
            title: "Volume",
          },
          {
            icon: "Pin",
            pageName: "wilayah-angkut-list",
            title: "Wilayah Angkut",
          },
          {
            icon: "Ship",
            pageName: "kapals-list",
            title: "Master Kapal",
          },
          {
            icon: "Ship",
            pageName: "ongkos-kapal-list",
            title: "OA Kapal",
          },
          {
            icon: "Truck",
            pageName: "trucks-list",
            title: "Master Truck",
          },
          {
            icon: "Truck",
            pageName: "oa-trucks-list",
            title: "OA Truck",
          },
        ],
      },
      {
        icon: "File",
        pageName: "review-data-customer-logistik",
        title: "Review Data Customer",
      },
    ],
  },

  /* Section: Master Data */
  {
    icon: "Database",
    title: "Master Data",
    subMenu: [
      {
        icon: "Archive",
        title: "Produk",
        subMenu: [
          {
            icon: "CornerDownRight",
            pageName: "produks-list",
            title: "Produk",
          },
          {
            icon: "CornerDownRight",
            pageName: "satuan",
            title: "Satuan",
          },
          {
            icon: "CornerDownRight",
            pageName: "ukuran",
            title: "Ukuran",
          },
          {
            icon: "CornerDownRight",
            pageName: "jenis-produk-list",
            title: "Jenis",
          },
        ],
      },
      {
        icon: "Wallet",
        title: "Harga",
        subMenu: [
          {
            icon: "ShoppingBag",
            pageName: "produk-hargas",
            title: "Produk Harga",
            activePageNames: [
              "produk-hargas-create",
              "produk-hargas-edit",
              "produk-hargas-detail",
            ],
          },
          {
            icon: "Tag",
            pageName: "attachment-harga-dasar-list",
            title: "Attachment Harga",
          },
        ],
      },
      {
        icon: "CreditCard",
        pageName: "vendors-list",
        title: "Vendor",
        activePageNames: ["vendors-create", "vendors-edit"],
      },
      {
        icon: "Terminal",
        pageName: "terminals-list",
        title: "Terminal",
      },
      {
        icon: "MapPin",
        title: "Wilayah",
        subMenu: [
          {
            icon: "Flag",
            pageName: "provinsi-list",
            title: "Provinsi",
          },
          {
            icon: "Flag",
            pageName: "kabupatens-list",
            title: "Kabupaten",
          },
        ],
      },
    ],
  },

  /* Section: Tata Kelola */
  {
    icon: "Shield",
    title: "Tata Kelola",
    subMenu: [
      {
        icon: "User",
        title: "Access Control",
        subMenu: [
          {
            icon: "Shield",
            pageName: "role-overview",
            title: "Role",
          },
          {
            icon: "Users",
            pageName: "users",
            title: "User",
          },
        ],
      },
      {
        icon: "Monitor",
        pageName: "monitoring-app-logs",
        title: "Monitoring",
      },
      {
        icon: "File",
        pageName: "review-data-customer-admin",
        title: "Review Data Customer",
      },
    ],
  },
];

export default menu;
