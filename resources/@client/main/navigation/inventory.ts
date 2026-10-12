import type { NavSection } from "@/stores/menu";

export const inventorySection: NavSection = {
  key: "inventory",
  title: "Inventory",
  icon: "Warehouse",
  items: [
    {
      icon: "Warehouse",
      pageName: "StockInventory",
      title: "Inventory",
      permission: "inventory.view",
    },
  ],
};
