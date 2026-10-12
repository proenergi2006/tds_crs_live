import type { NavSection } from "@/stores/menu";

export const accessControlSection: NavSection = {
  key: "access-control",
  title: "Access Control",
  icon: "ShieldEllipsis",
  items: [
    {
      icon: "UserCog",
      pageName: "role-overview",
      title: "Role",
      permission: "admin.users.manage",
    },
    {
      icon: "KeyRound",
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
};

export const configSection: NavSection = {
  key: "config",
  title: "Config",
  icon: "Settings",
  items: [
    {
      icon: "ScrollText",
      pageName: "monitoring-app-logs",
      title: "Application Logs",
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
};

export const dataManagementSection: NavSection = {
  key: "data-management",
  title: "Data Management",
  icon: "Database",
  items: [
    {
      icon: "ArrowRightLeft",
      pageName: "customer-migration",
      title: "Customer Migration",
      permission: "admin.customer-migration.manage",
    },
  ],
};
