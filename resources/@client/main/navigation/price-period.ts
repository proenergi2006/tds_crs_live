import type { NavItem } from "./types";

export const pricePeriodNavigation: NavItem[] = [
  {
    icon: "Wallet",
    pageName: "product-prices",
    title: "Periode Harga",
    activePageNames: [
      "product-prices-create",
      "product-prices-edit",
    ],
    permission: "price-period.view",
    excludeRoles: [2],
  },
];
