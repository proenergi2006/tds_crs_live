import type { NavItem } from "./types";

export const masterDataNavigation: NavItem[] = [
  {
    icon: "Database",
    title: "Master Data",
    subMenu: [
      {
        icon: "CreditCard",
        pageName: "vendors-list",
        title: "Vendor",
        activePageNames: ["vendors-create", "vendors-edit"],
        permission: "master-data.view",
      },
      {
        icon: "Terminal",
        pageName: "terminals-list",
        title: "Terminal",
        permission: "master-data.view",
      },
      {
        icon: "Archive",
        title: "Produk",
        subMenu: [
          {
            icon: "CornerDownRight",
            pageName: "produks-list",
            title: "Produk",
            permission: "master-data.view",
          },
          {
            icon: "CornerDownRight",
            pageName: "satuan",
            title: "Satuan",
            permission: "master-data.view",
          },
          {
            icon: "CornerDownRight",
            pageName: "ukuran",
            title: "Ukuran",
            permission: "master-data.view",
          },
          {
            icon: "CornerDownRight",
            pageName: "jenis-produk-list",
            title: "Jenis",
            permission: "master-data.view",
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
            title: "Periode Harga",
            activePageNames: [
              "produk-hargas-create",
              "produk-hargas-edit",
              "produk-hargas-detail",
            ],
            permission: "harga-produk.view",
            // CEO liatnya di grup Verifikasi (verification.ts), bukan di sini -- biar gak dobel
            excludeRoles: [2],
          },
          {
            icon: "Tag",
            pageName: "attachment-harga-dasar-list",
            title: "Attachment Harga",
            permission: "harga-produk.view",
          },
        ],
      },
      {
        icon: "Building2",
        pageName: "cabang",
        title: "Cabang",
        permission: "master-data.cabang.manage",
      },
      {
        icon: "MapPin",
        title: "Wilayah",
        subMenu: [
          {
            icon: "Globe",
            pageName: "master-address",
            title: "Master Address",
            permission: "master-data.address.view",
          },
        ],
      },
      {
        icon: "Users",
        title: "Customer",
        subMenu: [
          {
            icon: "FileText",
            pageName: "customer-document-types",
            title: "Jenis Dokumen",
            permission: "master-data.customer-document-type.manage",
          },
        ],
      },
    ],
  },
];
