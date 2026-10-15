import type { QuotationPriceTier } from "@/types";

export const QUOTATION_VERIFICATION_INDEX = {
  title: "Daftar Verifikasi Penawaran",
  description: "Monitor penawaran yang menunggu verifikasi.",
  endpoint: "/quotation-verifications",
  detailRouteName: "verification.quotations.show",
};

export const QUOTATION_VERIFICATION_DETAIL = {
  title: "Verifikasi Penawaran",
  fetchEndpoint: "/quotations",
  verifyEndpoint: (id: number) => `/quotation-verifications/${id}/approve`,
  rejectEndpoint: (id: number) => `/quotation-verifications/${id}/reject`,
};

export const PRICE_TIER_FILTERS: { value: QuotationPriceTier | null; label: string }[] = [
  { value: null, label: "Semua" },
  { value: "tier_1", label: "Tier I" },
  { value: "tier_2", label: "Tier II" },
  { value: "tier_3", label: "Tier III" },
];
