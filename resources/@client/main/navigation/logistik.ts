import type { NavSection } from "@/stores/menu";

export const logistikSection: NavSection = {
  key: "logistik",
  title: "Logistics",
  icon: "Truck",
  items: [
    {
      icon: "MapPin",
      pageName: "logistik-lcrs",
      title: "LCR Verification",
      permission: "logistik.lcr.verify",
    },
    {
      icon: "Route",
      pageName: "logistics-delivery-plan",
      title: "Delivery Plan",
      permission: "logistik.delivery-plan.view",
    },
    {
      icon: "FolderCog",
      title: "Logistics Master Data",
      children: [
        { icon: "Building", pageName: "transporters-list", title: "Transporter", permission: "logistik.master.manage" },
        { icon: "HardHat", pageName: "personnels-list", title: "Personnel", permission: "logistik.master.manage" },
        { icon: "Container", pageName: "volumes-list", title: "Volume", permission: "logistik.master.manage" },
        { icon: "Map", pageName: "transport-areas-list", title: "Transport Area", permission: "logistik.master.manage" },
        { icon: "Ship", pageName: "vessels-list", title: "Vessel", permission: "logistik.master.manage" },
        { icon: "Truck", pageName: "trucks-list", title: "Truck", permission: "logistik.master.manage" },
        { icon: "Coins", pageName: "transport-tariffs-list", title: "Transport Tariff", permission: "logistik.master.manage" },
      ],
    },
  ],
};
