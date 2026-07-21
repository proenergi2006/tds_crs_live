import { ref } from "vue";
import axios from "axios";

export interface RegionOption {
  id: string;
  name: string;
}

export function useRegionCascade() {
  const provinces = ref<RegionOption[]>([]);
  const regencies = ref<RegionOption[]>([]);
  const districts = ref<RegionOption[]>([]);
  const villages = ref<RegionOption[]>([]);

  const loadingProvinces = ref(false);
  const loadingRegencies = ref(false);
  const loadingDistricts = ref(false);
  const loadingVillages = ref(false);

  function extractList(raw: any): RegionOption[] {
    return Array.isArray(raw?.data) ? raw.data : [];
  }

  async function fetchProvinces() {
    loadingProvinces.value = true;
    try {
      const { data } = await axios.get("/api/provinces");
      provinces.value = extractList(data);
    } catch {
      provinces.value = [];
    } finally {
      loadingProvinces.value = false;
    }
  }

  async function fetchRegencies(provinceId: string | null | undefined) {
    regencies.value = [];
    if (!provinceId) return;
    loadingRegencies.value = true;
    try {
      const { data } = await axios.get(
        `/api/provinces/${provinceId}/regencies`,
      );
      regencies.value = extractList(data);
    } catch {
      regencies.value = [];
    } finally {
      loadingRegencies.value = false;
    }
  }

  async function fetchDistricts(regencyId: string | null | undefined) {
    districts.value = [];
    if (!regencyId) return;
    loadingDistricts.value = true;
    try {
      const { data } = await axios.get(`/api/regencies/${regencyId}/districts`);
      districts.value = extractList(data);
    } catch {
      districts.value = [];
    } finally {
      loadingDistricts.value = false;
    }
  }

  async function fetchVillages(districtId: string | null | undefined) {
    villages.value = [];
    if (!districtId) return;
    loadingVillages.value = true;
    try {
      const { data } = await axios.get(`/api/districts/${districtId}/villages`);
      villages.value = extractList(data);
    } catch {
      villages.value = [];
    } finally {
      loadingVillages.value = false;
    }
  }

  return {
    provinces,
    regencies,
    districts,
    villages,
    loadingProvinces,
    loadingRegencies,
    loadingDistricts,
    loadingVillages,
    fetchProvinces,
    fetchRegencies,
    fetchDistricts,
    fetchVillages,
  };
}
