export interface OnboardingIdentity {
  company_name: string;
  company_address: string;
  phone: string;
  fax: string;
  email: string;
  website: string;
  business_type: string;
  business_type_other: string;
  ownership_type: string;
  ownership_type_other: string;
  parent_company: string;
  province_id: string | null;
  regency_id: string | null;
  district_id: string | null;
  village_id: string | null;
  postal_code: string;
  inco_terms: string;
  inco_terms_other: string;
}

export interface OnboardingAddress {
  address_line: string;
  province_id: string | null;
  regency_id: string | null;
  district_id: string | null;
  village_id: string | null;
  postal_code: string;
}

export interface OnboardingInvoiceContact {
  name: string;
  position: string;
  phone: string;
  mobile: string;
  email: string;
}

export interface OnboardingPayment {
  method: string;
  method_other: string;
  invoice_tax: boolean;
  note: string;
  pricing_method: string;
  bank_name: string;
  currency: string;
  bank_address: string;
  account_number: string;
  has_credit: boolean;
  creditor_name: string;
  term: string;
  term_days: number | null;
  term_basis: string;
}

export interface OnboardingLogistics {
  site_environment: string;
  site_environment_other: string;
  site_environment_notes: string;
  storage_type: string;
  storage_type_other: string;
  storage_notes: string;
  operating_hours: string;
  operating_hours_other: string;
  quality_checking_method: string;
  quality_checking_notes: string;
  quantity_checking_method: string;
  quantity_checking_notes: string;
  max_truck_capacity_min: number | null;
  max_truck_capacity_max: number | null;
  supports_vessel_delivery: boolean;
  product_notes: string;
  estimated_monthly_volume: number | null;
}

export interface OnboardingDocumentSlot {
  file: File | null;
  number: string;
}

export interface OnboardingDocuments {
  nib: OnboardingDocumentSlot;
  npwp: OnboardingDocumentSlot;
  sertifikat: OnboardingDocumentSlot;
  dokumen_lainnya: File[];
}

export interface OnboardingAgreement {
  agree: boolean;
  updated_by: string;
}

export interface OnboardingForm {
  identity: OnboardingIdentity;
  registered_address: OnboardingAddress;
  invoice_contact: OnboardingInvoiceContact;
  payment: OnboardingPayment;
  logistics: OnboardingLogistics;
  documents: OnboardingDocuments;
  agreement: OnboardingAgreement;
}

export type OnboardingPageState =
  | "loading"
  | "active"
  | "used"
  | "expired"
  | "invalidated"
  | "not-found";
