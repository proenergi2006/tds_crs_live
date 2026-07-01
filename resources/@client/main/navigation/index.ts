import type { NavItem } from './types'
import { dashboardNavigation } from './dashboard'
import { customerNavigation } from './customer'
import { procurementNavigation } from './procurement'
import { verificationNavigation } from './verification'
import { logistikNavigation } from './logistik'
import { masterDataNavigation } from './master-data'
import { adminNavigation } from './admin'

const navigation: NavItem[] = [
  ...dashboardNavigation,
  ...customerNavigation,
  ...procurementNavigation,
  ...verificationNavigation,
  ...logistikNavigation,
  ...masterDataNavigation,
  ...adminNavigation,
]

export default navigation
export type { NavItem }
