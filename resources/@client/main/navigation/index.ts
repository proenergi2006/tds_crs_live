import type { NavSection } from "@/stores/menu";
import { generalSection } from "./dashboard";
import { commercialSection } from "./customer";
import { procurementSection } from "./procurement";
import { inventorySection } from "./inventory";
import { verificationSection } from "./verification";
import { logistikSection } from "./logistik";
import { pricingSection } from "./price-period";
import { masterDataSection } from "./master-data";
import { accessControlSection, configSection, dataManagementSection } from "./admin";

const navigation: NavSection[] = [
  generalSection,
  commercialSection,
  procurementSection,
  inventorySection,
  verificationSection,
  logistikSection,
  pricingSection,
  masterDataSection,
  accessControlSection,
  configSection,
  dataManagementSection,
];

export default navigation;
