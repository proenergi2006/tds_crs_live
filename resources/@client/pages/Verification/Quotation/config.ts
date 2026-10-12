import type { QuotationBrand } from "@/types";

export type VerifikasiRole = "bm" | "om" | "ceo";

export interface VerifikasiConfig {
  title: string;
  description: string;
  endpoint: string;
  detailRouteName: string;
}

export interface VerifikasiDetailConfig {
  title: string;
  fetchEndpoint: string;
  verifyEndpoint: (id: number) => string;
  rejectEndpoint: (id: number) => string;
}

const ENDPOINT_BASE: Record<QuotationBrand, string> = {
  tds: "/penawarans",
  proenergi: "/penawarans-proenergi",
};

export const VERIFIKASI_ROLE_LABEL: Record<VerifikasiRole, string> = {
  bm: "Branch Manager",
  om: "Operation Manager",
  ceo: "CEO",
};

export function verifikasiRolePermissions(
  role: VerifikasiRole,
  brand: QuotationBrand,
): string[] {
  if (brand === "proenergi") {
    switch (role) {
      case "bm":
        return ["penawaran.proenergi.verify-bm"];
      case "om":
        return ["penawaran.proenergi.verify-om"];
      case "ceo":
        return ["penawaran.verify-ceo"];
    }
  }

  switch (role) {
    case "bm":
      return ["penawaran.verify-bm", "penawaran.verify-bm-polimer"];
    case "om":
      return ["penawaran.verify-om"];
    case "ceo":
      return ["penawaran.verify-ceo"];
  }
}

export function getVerifikasiConfig(
  role: VerifikasiRole,
  brand: QuotationBrand,
): VerifikasiConfig {
  const isProenergi = brand === "proenergi";

  return {
    title: isProenergi
      ? "Daftar Verifikasi Penawaran Proenergi"
      : "Daftar Verifikasi Penawaran",
    description: `Monitor penawaran yang menunggu verifikasi ${VERIFIKASI_ROLE_LABEL[role]}.`,
    endpoint: `${ENDPOINT_BASE[brand]}/${role}`,
    detailRouteName: `penawarans-verifikasi-${role}-detail${isProenergi ? "-proenergi" : ""}`,
  };
}

export function getVerifikasiDetailConfig(
  role: VerifikasiRole,
  brand: QuotationBrand,
): VerifikasiDetailConfig {
  const isProenergi = brand === "proenergi";
  const base = ENDPOINT_BASE[brand];
  const isBm = role === "bm";

  return {
    title: `Verifikasi Penawaran ${VERIFIKASI_ROLE_LABEL[role]}${isProenergi ? " Proenergi" : ""}`,
    fetchEndpoint: base,
    verifyEndpoint: (id) => `${base}/${id}/${isBm ? "verifikasi" : "verifikasi-om"}`,
    rejectEndpoint: (id) => `${base}/${id}/${isBm ? "tolak-bm" : "tolak-om"}`,
  };
}
