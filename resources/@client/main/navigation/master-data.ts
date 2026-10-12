import type { NavSection } from "@/stores/menu";

export const masterDataSection: NavSection = {
  key: "master-data",
  title: "Master Data",
  icon: "Database",
  items: [
    {
      icon: "Store",
      pageName: "vendors-list",
      title: "Vendor",
      activePageNames: ["vendors-create", "vendors-edit"],
      permission: "master-data.view",
    },
    {
      icon: "Anchor",
      pageName: "terminals-list",
      title: "Terminal",
      permission: "master-data.view",
    },
    {
      icon: "Package",
      pageName: "produks-list",
      title: "Product",
      permission: "master-data.view",
      children: [
        {
          icon: "Scale",
          pageName: "satuan",
          title: "Unit",
          permission: "master-data.view",
        },
        {
          icon: "Ruler",
          pageName: "ukuran",
          title: "Size",
          permission: "master-data.view",
        },
        {
          icon: "Shapes",
          pageName: "jenis-produk-list",
          title: "Product Type",
          permission: "master-data.view",
        },
      ],
    },
    {
      icon: "Building2",
      pageName: "cabang",
      title: "Branch",
      permission: "master-data.cabang.manage",
    },
    {
      icon: "Globe",
      pageName: "master-address",
      title: "Address",
      permission: "master-data.address.view",
    },
    {
      icon: "FolderOpen",
      pageName: "customer-document-types",
      title: "Customer Document Type",
      permission: "master-data.customer-document-type.manage",
    },
  ],
};
