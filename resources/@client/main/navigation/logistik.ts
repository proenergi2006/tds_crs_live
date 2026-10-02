import type { NavItem } from './types'

export const logistikNavigation: NavItem[] = [
  {
    icon: 'Truck',
    title: 'Logistik',
    subMenu: [
      {
        icon: 'MapPin',
        pageName: 'logistik-lcrs',
        title: 'Verifikasi LCR',
        permission: 'logistik.lcr.verify',
      },
      {
        icon: 'ClipboardList',
        pageName: 'logistics-delivery-plan',
        title: 'Delivery Plan',
        permission: 'logistik.delivery-plan.view',
      },
      {
        icon: 'Database',
        title: 'Logistics Master Data',
        subMenu: [
          { icon: 'Users',   pageName: 'transporters-list',      title: 'Transporter',      permission: 'logistik.master.manage' },
          { icon: 'User',    pageName: 'personnels-list',        title: 'Personnel',        permission: 'logistik.master.manage' },
          { icon: 'Package', pageName: 'volumes-list',           title: 'Volume',           permission: 'logistik.master.manage' },
          { icon: 'Pin',     pageName: 'transport-areas-list',   title: 'Transport Area',   permission: 'logistik.master.manage' },
          { icon: 'Ship',    pageName: 'vessels-list',           title: 'Vessel',           permission: 'logistik.master.manage' },
          { icon: 'Truck',   pageName: 'trucks-list',            title: 'Truck',            permission: 'logistik.master.manage' },
          { icon: 'Coins',   pageName: 'transport-tariffs-list', title: 'Transport Tariff', permission: 'logistik.master.manage' },
        ],
      },
    ],
  },
]
