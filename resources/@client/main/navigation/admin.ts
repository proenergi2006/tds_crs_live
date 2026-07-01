import type { NavItem } from './types'

export const adminNavigation: NavItem[] = [
  {
    icon: 'Shield',
    title: 'Tata Kelola',
    subMenu: [
      {
        icon: 'User',
        title: 'Access Control',
        subMenu: [
          { icon: 'Shield', pageName: 'role-overview', title: 'Role', permission: 'admin.users.manage' },
          { icon: 'Users',  pageName: 'users',         title: 'User', permission: 'admin.users.manage' },
        ],
      },
      {
        icon: 'Monitor',
        pageName: 'monitoring-app-logs',
        title: 'Monitoring',
        permission: 'admin.monitoring.view',
      },
      {
        icon: 'File',
        pageName: 'review-data-customer-admin',
        title: 'Review Data Customer',
        permission: 'sales-confirmation.manage',
      },
    ],
  },
]
