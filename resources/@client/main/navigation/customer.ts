import type { NavSection } from "@/stores/menu";
import { quotationPermissionsForAllBrands } from "@/utils/quotation-brand";

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
      pageName: "quotations.index",
      title: "Quotation",
      activePageNames: [
        "quotations.create",
        "quotations.edit",
        "quotations.show",
      ],
      permission: quotationPermissionsForAllBrands("viewOwn"),
    },
    {
      icon: "ShoppingCart",
      pageName: "po-customers-index",
      title: "PO Customer",
      activePageNames: ["po-customers-detail", "po-customers-edit", "penawarans-po"],
      permission: [
        ...quotationPermissionsForAllBrands("viewAny"),
        ...quotationPermissionsForAllBrands("manage"),
        "sales-confirmation.manage",
      ],
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
