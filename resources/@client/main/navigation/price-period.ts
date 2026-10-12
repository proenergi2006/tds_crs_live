import type { NavSection } from "@/stores/menu";

export const pricingSection: NavSection = {
  key: "pricing",
  title: "Pricing",
  icon: "Tags",
  items: [
    {
      icon: "Tags",
      pageName: "product-prices",
      title: "Price Period",
      activePageNames: [
        "product-prices-create",
        "product-prices-edit",
      ],
      permission: "price-period.view",
      excludeRoles: [2],
    },
    {
      icon: "Truck",
      pageName: "transport-margins",
      title: "Margin Transport",
      permission: "product-price.manage-transport-margin",
    },
    {
      icon: "Coins",
      pageName: "transport-tariffs-list",
      title: "Transport Tariff",
      permission: "logistik.transport-tariff.view",
    },
  ],
};
