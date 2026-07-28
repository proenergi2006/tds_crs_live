import type { NavItem } from "./types";

export const adminNavigation: NavItem[] = [
  {
    icon: "ShieldEllipsis",
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
    icon: "Settings",
    title: "Config",
    subMenu: [
      {
        icon: "ServerCog",
        pageName: "monitoring-app-logs",
        title: "App Logs",
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
  {
    icon: "Database",
    title: "Data Management",
    subMenu: [
      {
        icon: "ArrowRightLeft",
        pageName: "customer-migration",
        title: "Migrasi Customer",
        permission: "admin.customer-migration.manage",
      },
    ],
  },
];
