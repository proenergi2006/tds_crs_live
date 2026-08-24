<script setup lang="ts">
import { reactive, ref, computed, watch } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import { FormCheck, FormInput, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useRegionCascade } from '@/composables/useRegionCascade'

const props = defineProps<{
  customer: any
  idCustomer: number
}>()

const emit = defineEmits<{ (e: 'updated'): void }>()

const { success, error: notifyError } = useNotification()

function regionName(item: { name?: string } | null | undefined): string | null {
  return item?.name ?? null
}

// baris region + kode pos jadi satu teks mengalir -- koma antar level region, kode pos disambung spasi di akhir
function formatAddressRegionLine(region: {
  province?: { name?: string } | null
  regency?: { name?: string } | null
  district?: { name?: string } | null
  village?: { name?: string } | null
  postal_code?: string | null
}): string {
  const parts = [
    regionName(region.village),
    regionName(region.district),
    regionName(region.regency),
    regionName(region.province),
  ].filter(Boolean)
  const regionLine = parts.join(', ')
  if (!region.postal_code) return regionLine
  return regionLine ? `${regionLine} ${region.postal_code}` : region.postal_code
}

function resolveErrorMessage(e: any, fallback: string): string {
  if (e.response?.status === 422) {
    const errors = e.response?.data?.errors || {}
    return (Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.'
  }
  return e.response?.data?.message ?? fallback
}

const npwpAddressRecord = computed(() =>
  (props.customer?.addresses || []).find((a: any) => a.address_type === 'registered_npwp') || {},
)
const npwpAddressLine1 = computed(() => npwpAddressRecord.value.address_line || '')
const npwpAddressLine2 = computed(() => formatAddressRegionLine(npwpAddressRecord.value))
const hasNpwpAddress = computed(() => !!(npwpAddressLine1.value || npwpAddressLine2.value))

const headOfficeAddressLine1 = computed(() => props.customer?.company_address || '')
const headOfficeAddressLine2 = computed(() => formatAddressRegionLine(props.customer || {}))
const hasHeadOfficeAddress = computed(() => !!(headOfficeAddressLine1.value || headOfficeAddressLine2.value))

/* State: modal edit Alamat Head Office -- endpoint mandiri, sama polanya dengan alamat NPWP di bawah */
const headOfficeFormOpen = ref(false)
const savingHeadOffice = ref(false)
const isHydratingHeadOfficeRegion = ref(false)
const headOfficeRegion = useRegionCascade()
const headOfficeForm = reactive({
  address_line: '',
  province_id: '',
  regency_id: '',
  district_id: '',
  village_id: '',
  postal_code: '',
})

watch(() => headOfficeForm.province_id, async (newProv) => {
  if (isHydratingHeadOfficeRegion.value) return
  headOfficeForm.regency_id = ''
  headOfficeForm.district_id = ''
  headOfficeForm.village_id = ''
  await headOfficeRegion.fetchRegencies(newProv || null)
})
watch(() => headOfficeForm.regency_id, async (newRegency) => {
  if (isHydratingHeadOfficeRegion.value) return
  headOfficeForm.district_id = ''
  headOfficeForm.village_id = ''
  await headOfficeRegion.fetchDistricts(newRegency || null)
})
watch(() => headOfficeForm.district_id, async (newDistrict) => {
  if (isHydratingHeadOfficeRegion.value) return
  headOfficeForm.village_id = ''
  await headOfficeRegion.fetchVillages(newDistrict || null)
})
watch(() => headOfficeForm.village_id, (newVillage) => {
  if (isHydratingHeadOfficeRegion.value || !newVillage) return
  const matched = headOfficeRegion.villages.value.find(v => v.id === newVillage)
  if (matched?.postal_code) headOfficeForm.postal_code = matched.postal_code
})

async function startEditHeadOffice() {
  const cust = props.customer || {}
  isHydratingHeadOfficeRegion.value = true
  try {
    await headOfficeRegion.fetchProvinces()
    headOfficeForm.address_line = cust.company_address ?? ''
    headOfficeForm.postal_code = cust.postal_code ?? ''
    headOfficeForm.province_id = cust.province_id ? String(cust.province_id) : ''
    headOfficeForm.regency_id = ''
    headOfficeForm.district_id = ''
    headOfficeForm.village_id = ''
    if (headOfficeForm.province_id) {
      await headOfficeRegion.fetchRegencies(headOfficeForm.province_id)
      headOfficeForm.regency_id = cust.regency_id ? String(cust.regency_id) : ''
      if (headOfficeForm.regency_id) {
        await headOfficeRegion.fetchDistricts(headOfficeForm.regency_id)
        headOfficeForm.district_id = cust.district_id ? String(cust.district_id) : ''
        if (headOfficeForm.district_id) {
          await headOfficeRegion.fetchVillages(headOfficeForm.district_id)
          headOfficeForm.village_id = cust.village_id ? String(cust.village_id) : ''
        }
      }
    }
  } finally {
    isHydratingHeadOfficeRegion.value = false
  }
  headOfficeFormOpen.value = true
}

function cancelEditHeadOffice() {
  headOfficeFormOpen.value = false
}

async function submitHeadOfficeForm() {
  savingHeadOffice.value = true
  try {
    await axios.put(`/api/customers/${props.idCustomer}/addresses/head_office`, { ...headOfficeForm })
    headOfficeFormOpen.value = false
    success('Berhasil', 'Alamat Head Office berhasil diperbarui.')
    emit('updated')
  } catch (e: any) {
    notifyError('Gagal', resolveErrorMessage(e, 'Gagal memperbarui Alamat Head Office.'))
  } finally {
    savingHeadOffice.value = false
  }
}

/* State: modal edit Alamat NPWP -- endpoint mandiri, aman tanpa embed field lain */
const npwpFormOpen = ref(false)
const savingNpwp = ref(false)
const isHydratingNpwpRegion = ref(false)
const npwpRegion = useRegionCascade()
const npwpForm = reactive({
  address_line: '',
  province_id: '',
  regency_id: '',
  district_id: '',
  village_id: '',
  postal_code: '',
})
const npwpSameAsHeadOffice = ref(false)

watch(() => npwpForm.province_id, async (newProv) => {
  if (isHydratingNpwpRegion.value) return
  npwpForm.regency_id = ''
  npwpForm.district_id = ''
  npwpForm.village_id = ''
  await npwpRegion.fetchRegencies(newProv || null)
})
watch(() => npwpForm.regency_id, async (newRegency) => {
  if (isHydratingNpwpRegion.value) return
  npwpForm.district_id = ''
  npwpForm.village_id = ''
  await npwpRegion.fetchDistricts(newRegency || null)
})
watch(() => npwpForm.district_id, async (newDistrict) => {
  if (isHydratingNpwpRegion.value) return
  npwpForm.village_id = ''
  await npwpRegion.fetchVillages(newDistrict || null)
})
watch(() => npwpForm.village_id, (newVillage) => {
  if (isHydratingNpwpRegion.value || !newVillage) return
  const matched = npwpRegion.villages.value.find(v => v.id === newVillage)
  if (matched?.postal_code) npwpForm.postal_code = matched.postal_code
})

async function copyHeadOfficeAddressToNpwp() {
  const cust = props.customer || {}
  isHydratingNpwpRegion.value = true
  try {
    npwpForm.address_line = cust.company_address ?? ''
    npwpForm.postal_code = cust.postal_code ?? ''
    npwpForm.province_id = cust.province_id ? String(cust.province_id) : ''
    npwpForm.regency_id = ''
    npwpForm.district_id = ''
    npwpForm.village_id = ''
    if (npwpForm.province_id) {
      await npwpRegion.fetchRegencies(npwpForm.province_id)
      npwpForm.regency_id = cust.regency_id ? String(cust.regency_id) : ''
      if (npwpForm.regency_id) {
        await npwpRegion.fetchDistricts(npwpForm.regency_id)
        npwpForm.district_id = cust.district_id ? String(cust.district_id) : ''
        if (npwpForm.district_id) {
          await npwpRegion.fetchVillages(npwpForm.district_id)
          npwpForm.village_id = cust.village_id ? String(cust.village_id) : ''
        }
      }
    }
  } finally {
    isHydratingNpwpRegion.value = false
  }
}

function resetNpwpAddressFields() {
  npwpForm.address_line = ''
  npwpForm.postal_code = ''
  npwpForm.province_id = ''
  npwpForm.regency_id = ''
  npwpForm.district_id = ''
  npwpForm.village_id = ''
  npwpRegion.regencies.value = []
  npwpRegion.districts.value = []
  npwpRegion.villages.value = []
}

watch(npwpSameAsHeadOffice, (checked) => {
  if (checked) copyHeadOfficeAddressToNpwp()
  else resetNpwpAddressFields()
})

async function startEditNpwp() {
  const npwpAddress = (props.customer?.addresses || []).find((a: any) => a.address_type === 'registered_npwp') || {}
  npwpSameAsHeadOffice.value = false
  isHydratingNpwpRegion.value = true
  try {
    await npwpRegion.fetchProvinces()
    npwpForm.address_line = npwpAddress.address_line ?? ''
    npwpForm.postal_code = npwpAddress.postal_code ?? ''
    npwpForm.province_id = npwpAddress.province_id ? String(npwpAddress.province_id) : ''
    npwpForm.regency_id = ''
    npwpForm.district_id = ''
    npwpForm.village_id = ''
    if (npwpForm.province_id) {
      await npwpRegion.fetchRegencies(npwpForm.province_id)
      npwpForm.regency_id = npwpAddress.regency_id ? String(npwpAddress.regency_id) : ''
      if (npwpForm.regency_id) {
        await npwpRegion.fetchDistricts(npwpForm.regency_id)
        npwpForm.district_id = npwpAddress.district_id ? String(npwpAddress.district_id) : ''
        if (npwpForm.district_id) {
          await npwpRegion.fetchVillages(npwpForm.district_id)
          npwpForm.village_id = npwpAddress.village_id ? String(npwpAddress.village_id) : ''
        }
      }
    }
  } finally {
    isHydratingNpwpRegion.value = false
  }
  npwpFormOpen.value = true
}

function cancelEditNpwp() {
  npwpFormOpen.value = false
}

async function submitNpwpForm() {
  savingNpwp.value = true
  try {
    await axios.put(`/api/customers/${props.idCustomer}/addresses/registered_npwp`, npwpForm)
    npwpFormOpen.value = false
    success('Berhasil', 'Alamat NPWP berhasil diperbarui.')
    emit('updated')
  } catch (e: any) {
    notifyError('Gagal', resolveErrorMessage(e, 'Gagal memperbarui Alamat NPWP.'))
  } finally {
    savingNpwp.value = false
  }
}
</script>

<template>
  <CardSection title="Alamat" description="Alamat Head Office & alamat NPWP terdaftar." icon="MapPin"
    icon-class="bg-sky-100 text-sky-600">
    <div class="gap-4 grid sm:grid-cols-2">
      <div class="p-4 border border-slate-200 rounded-lg">
        <div class="flex justify-between items-center gap-3">
          <div class="flex items-center gap-3">
            <div class="flex justify-center items-center bg-sky-100 rounded-full w-10 h-10 text-sky-600 shrink-0">
              <Lucide icon="Building2" class="w-5 h-5" />
            </div>
            <div class="font-strong">Head Office</div>
          </div>
          <Button size="sm" variant="outline-secondary" title="Edit" class="!shadow-none !p-0 !w-8 !h-8"
            @click="startEditHeadOffice">
            <Lucide icon="Edit" class="w-4 h-4" />
          </Button>
        </div>

        <div class="mt-4">
          <template v-if="hasHeadOfficeAddress">
            <p v-if="headOfficeAddressLine1" class="font-body">{{ headOfficeAddressLine1 }}</p>
            <p v-if="headOfficeAddressLine2" class="mt-3 font-body">{{ headOfficeAddressLine2 }}</p>
          </template>
          <p v-else class="font-body text-slate-400">Alamat belum diisi.</p>
        </div>
      </div>

      <div class="p-4 border border-slate-200 rounded-lg">
        <div class="flex justify-between items-center gap-3">
          <div class="flex items-center gap-3">
            <div class="flex justify-center items-center bg-sky-100 rounded-full w-10 h-10 text-sky-600 shrink-0">
              <Lucide icon="FileCheck2" class="w-5 h-5" />
            </div>
            <div class="font-strong">NPWP</div>
          </div>
          <Button size="sm" variant="outline-secondary" title="Edit" class="!shadow-none !p-0 !w-8 !h-8"
            @click="startEditNpwp">
            <Lucide icon="Edit" class="w-4 h-4" />
          </Button>
        </div>

        <div class="mt-4">
          <template v-if="hasNpwpAddress">
            <p v-if="npwpAddressLine1" class="font-body">{{ npwpAddressLine1 }}</p>
            <p v-if="npwpAddressLine2" class="mt-3 font-body">{{ npwpAddressLine2 }}</p>
          </template>
          <p v-else class="font-body text-slate-400">Alamat belum diisi.</p>
        </div>
      </div>
    </div>
  </CardSection>

  <FormModal :open="headOfficeFormOpen" title="Edit Alamat Head Office" description="Alamat kantor pusat customer."
    :loading="savingHeadOffice" submit-text="Simpan" submit-icon="Save" @close="cancelEditHeadOffice"
    @submit="submitHeadOfficeForm">
    <div class="flex justify-between items-start gap-4 pb-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Alamat</span>
      <div class="mt-0.5 w-1/2">
        <FormTextarea v-model="headOfficeForm.address_line" rows="2"
          placeholder="Nama jalan, nomor, gedung, lantai, dst." />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Provinsi</span>
      <div class="mt-0.5 w-1/2">
        <TomSelect v-model="headOfficeForm.province_id" class="w-full">
          <option value="">Cari Provinsi</option>
          <option v-for="p in headOfficeRegion.provinces.value" :key="p.id" :value="p.id">{{ p.name }}
          </option>
        </TomSelect>
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Kota/Kabupaten</span>
      <div class="mt-0.5 w-1/2">
        <TomSelect :key="String(!!headOfficeForm.province_id)" v-model="headOfficeForm.regency_id" class="w-full"
          :disabled="!headOfficeForm.province_id">
          <option value="">{{ headOfficeForm.province_id ? 'Cari Kabupaten/Kota' : '-- Pilih Provinsi dulu --'
          }}</option>
          <option v-for="k in headOfficeRegion.regencies.value" :key="k.id" :value="k.id">{{ k.name }}
          </option>
        </TomSelect>
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Kecamatan</span>
      <div class="mt-0.5 w-1/2">
        <TomSelect :key="String(!!headOfficeForm.regency_id)" v-model="headOfficeForm.district_id" class="w-full"
          :disabled="!headOfficeForm.regency_id">
          <option value="">{{ headOfficeForm.regency_id ? 'Cari Kecamatan' : '-- Pilih Kabupaten/Kota dulu --'
          }}</option>
          <option v-for="d in headOfficeRegion.districts.value" :key="d.id" :value="d.id">{{ d.name }}
          </option>
        </TomSelect>
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Kelurahan</span>
      <div class="mt-0.5 w-1/2">
        <TomSelect :key="String(!!headOfficeForm.district_id)" v-model="headOfficeForm.village_id" class="w-full"
          :disabled="!headOfficeForm.district_id">
          <option value="">
            {{ headOfficeForm.district_id ? 'Cari Kelurahan/Desa' : '-- Pilih Kecamatan dulu --' }}
          </option>
          <option v-for="v in headOfficeRegion.villages.value" :key="v.id" :value="v.id">{{ v.name }}
          </option>
        </TomSelect>
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 pb-1.5">
      <span class="font-label text-[14px]">Kode Pos</span>
      <div class="mt-0.5 w-1/2">
        <FormInput v-model="headOfficeForm.postal_code" placeholder="cth. 40111" />
      </div>
    </div>
  </FormModal>

  <FormModal :open="npwpFormOpen" title="Edit Alamat NPWP" description="Alamat NPWP terdaftar customer."
    :loading="savingNpwp" submit-text="Simpan" submit-icon="Save" @close="cancelEditNpwp" @submit="submitNpwpForm">
    <FormCheck class="mb-4">
      <FormCheck.Input id="npwp-same-as-head-office" type="checkbox" v-model="npwpSameAsHeadOffice"
        :disabled="!hasHeadOfficeAddress" />
      <FormCheck.Label htmlFor="npwp-same-as-head-office">Sama dengan alamat Head Office</FormCheck.Label>
    </FormCheck>
    <p v-if="!hasHeadOfficeAddress" class="mb-4 font-caption !text-amber-600">
      Alamat Head Office belum diisi. Isi alamat Head Office terlebih dahulu untuk menggunakan opsi ini.
    </p>
    <div v-if="!npwpSameAsHeadOffice">
      <div class="flex justify-between items-start gap-4 pb-1.5 border-slate-100 border-b">
        <span class="font-label text-[14px]">Alamat NPWP</span>
        <div class="mt-0.5 w-1/2">
          <FormTextarea v-model="npwpForm.address_line" rows="2" auto-resize
            placeholder="Nama jalan, nomor, gedung, lantai, dst." />
        </div>
      </div>
      <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
        <span class="font-label text-[14px]">Provinsi</span>
        <div class="mt-0.5 w-1/2">
          <TomSelect v-model="npwpForm.province_id" class="w-full">
            <option value="">Cari Provinsi</option>
            <option v-for="p in npwpRegion.provinces.value" :key="p.id" :value="p.id">{{ p.name }}</option>
          </TomSelect>
        </div>
      </div>
      <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
        <span class="font-label text-[14px]">Kota/Kabupaten</span>
        <div class="mt-0.5 w-1/2">
          <TomSelect :key="String(!!npwpForm.province_id)" v-model="npwpForm.regency_id" class="w-full"
            :disabled="!npwpForm.province_id">
            <option value="">{{ npwpForm.province_id ? 'Cari Kabupaten/Kota' : '-- Pilih Provinsi dulu --' }}
            </option>
            <option v-for="k in npwpRegion.regencies.value" :key="k.id" :value="k.id">{{ k.name }}</option>
          </TomSelect>
        </div>
      </div>
      <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
        <span class="font-label text-[14px]">Kecamatan</span>
        <div class="mt-0.5 w-1/2">
          <TomSelect :key="String(!!npwpForm.regency_id)" v-model="npwpForm.district_id" class="w-full"
            :disabled="!npwpForm.regency_id">
            <option value="">{{ npwpForm.regency_id ? 'Cari Kecamatan' : '-- Pilih Kabupaten/Kota dulu --' }}
            </option>
            <option v-for="d in npwpRegion.districts.value" :key="d.id" :value="d.id">{{ d.name }}</option>
          </TomSelect>
        </div>
      </div>
      <div class="flex justify-between items-center gap-4 pb-1.5 border-slate-100 border-b">
        <span class="font-label text-[14px]">Kelurahan</span>
        <div class="mt-0.5 w-1/2">
          <TomSelect :key="String(!!npwpForm.district_id)" v-model="npwpForm.village_id" class="w-full"
            :disabled="!npwpForm.district_id">
            <option value="">
              {{ npwpForm.district_id ? 'Cari Kelurahan/Desa' : '-- Pilih Kecamatan dulu --' }}
            </option>
            <option v-for="v in npwpRegion.villages.value" :key="v.id" :value="v.id">{{ v.name }}</option>
          </TomSelect>
        </div>
      </div>
      <div class="flex justify-between items-center gap-4 pb-1.5">
        <span class="font-label text-[14px]">Kode Pos</span>
        <div class="mt-0.5 w-1/2">
          <FormInput v-model="npwpForm.postal_code" placeholder="cth. 40111" />
        </div>
      </div>
    </div>
  </FormModal>
</template>
