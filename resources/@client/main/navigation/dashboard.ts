import type { NavItem } from './types'

export const dashboardNavigation: NavItem[] = [
  {
    icon: 'Home',
    title: 'Dashboard',
    subMenu: [
      {
        icon: 'User',
        pageName: 'profile-overview-1',
        title: 'Profile',
      },
      {
        icon: 'Activity',
        pageName: 'dashboard-overview-1',
        title: 'Dashboard Utama',
      },
    ],
  },
]
