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
            title: "Produk Harga",
            activePageNames: [
              "produk-hargas-create",
              "produk-hargas-edit",
              "produk-hargas-detail",
            ],
            permission: "harga-produk.view",
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
        icon: "MapPin",
        title: "Wilayah",
        subMenu: [
          {
            icon: "Flag",
            pageName: "provinsi-list",
            title: "Provinsi",
            permission: "master-data.wilayah.manage",
          },
          {
            icon: "Flag",
            pageName: "kabupatens-list",
            title: "Kabupaten",
            permission: "master-data.wilayah.manage",
          },
          {
            icon: "Globe",
            pageName: "master-address",
            title: "Master Address",
            permission: "master-data.address.view",
          },
        ],
      },
    ],
  },
];
