import type { NavItem } from "./types";

export const adminNavigation: NavItem[] = [
  {
    icon: "Shield",
    title: "Access Control",
    subMenu: [
      {
        icon: "Shield",
        pageName: "role-overview",
        title: "Role",
        permission: "admin.users.manage",
      },
      {
        icon: "ShieldCheck",
        pageName: "permission-overview",
        title: "Permission",
        permission: "admin.users.manage",
      },
      {
        icon: "Users",
        pageName: "users",
        title: "User",
        permission: "admin.users.manage",
      },
    ],
  },
  {
    icon: "Shield",
    title: "Tata Kelola",
    subMenu: [
      {
        icon: "Monitor",
        pageName: "monitoring-app-logs",
        title: "Monitoring",
        permission: "admin.monitoring.view",
      },
      {
        icon: "Workflow",
        pageName: "approval-templates",
        title: "Approval Template",
        activePageNames: [
          "approval-templates-create",
          "approval-templates-edit",
        ],
        permission: "approval-template.manage",
      },
    ],
  },
];
