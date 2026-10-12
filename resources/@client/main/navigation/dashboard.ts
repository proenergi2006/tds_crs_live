import type { NavSection } from "@/stores/menu";

export const generalSection: NavSection = {
  key: "general",
  title: "General",
  icon: "LayoutDashboard",
  items: [
    {
      icon: "LayoutDashboard",
      pageName: "dashboard-overview-1",
      title: "Dashboard",
    },
  ],
};
