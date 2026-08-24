<script setup lang="ts">
import { ref, watch } from 'vue'

import Button from '@/components/Base/Button'
import { FormCheck, FormInput, FormLabel, FormTextarea } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import type { useRegionCascade } from '@/composables/useRegionCascade'

import type { OnboardingForm } from '../types'
import { incoTermsOptions, ownershipOptions, typeBusinessOptions } from '../optionSets'

const props = defineProps<{
  form: OnboardingForm
  errors: Record<string, string>
  headOfficeRegion: ReturnType<typeof useRegionCascade>
  npwpRegion: ReturnType<typeof useRegionCascade>
}>()

/* dropdownParent: 'body' -- halaman ini scroll container sendiri, dropdown TomSelect tinggi bikin blank space + scrollbar ganda kalau nempel di container (pola sama di Penawaran/Form.vue & Permission/Form.vue) */
const regionSelectOptions = { dropdownParent: 'body' as const }

/* Cascade: Head Office -- pakai @update:model-value bukan @change, soalnya tom-select.js clone <select> asli jadi native change gak pernah kepicu */
function onHeadOfficeProvinceChange(value: string | string[]) {
  const v = (Array.isArray(value) ? value[0] : value) || null
  props.form.identity.province_id = v
  props.form.identity.regency_id = null
  props.form.identity.district_id = null
  props.form.identity.village_id = null
  props.headOfficeRegion.fetchRegencies(v)
}
function onHeadOfficeRegencyChange(value: string | string[]) {
  const v = (Array.isArray(value) ? value[0] : value) || null
  props.form.identity.regency_id = v
  props.form.identity.district_id = null
  props.form.identity.village_id = null
  props.headOfficeRegion.fetchDistricts(v)
}
function onHeadOfficeDistrictChange(value: string | string[]) {
  const v = (Array.isArray(value) ? value[0] : value) || null
  props.form.identity.district_id = v
  props.form.identity.village_id = null
  props.headOfficeRegion.fetchVillages(v)
}
function onHeadOfficeVillageChange(value: string | string[]) {
  props.form.identity.village_id = (Array.isArray(value) ? value[0] : value) || null
  const matched = props.headOfficeRegion.villages.value.find(
    (v) => v.id === props.form.identity.village_id
  )
  if (matched && matched.postal_code) {
    props.form.identity.postal_code = matched.postal_code
  }
}

/* Cascade: NPWP */
function onNpwpProvinceChange(value: string | string[]) {
  const v = (Array.isArray(value) ? value[0] : value) || null
  props.form.registered_address.province_id = v
  props.form.registered_address.regency_id = null
  props.form.registered_address.district_id = null
  props.form.registered_address.village_id = null
  props.npwpRegion.fetchRegencies(v)
}
function onNpwpRegencyChange(value: string | string[]) {
  const v = (Array.isArray(value) ? value[0] : value) || null
  props.form.registered_address.regency_id = v
  props.form.registered_address.district_id = null
  props.form.registered_address.village_id = null
  props.npwpRegion.fetchDistricts(v)
}
function onNpwpDistrictChange(value: string | string[]) {
  const v = (Array.isArray(value) ? value[0] : value) || null
  props.form.registered_address.district_id = v
  props.form.registered_address.village_id = null
  props.npwpRegion.fetchVillages(v)
}
function onNpwpVillageChange(value: string | string[]) {
  props.form.registered_address.village_id = (Array.isArray(value) ? value[0] : value) || null
  const matched = props.npwpRegion.villages.value.find(
    (v) => v.id === props.form.registered_address.village_id
  )
  if (matched && matched.postal_code) {
    props.form.registered_address.postal_code = matched.postal_code
  }
}

/* Checkbox: NPWP address sama dengan Head Office */
const npwpSameAsHeadOffice = ref(false)

async function copyHeadOfficeToNpwp() {
  props.form.registered_address.address_line = props.form.identity.company_address
  props.form.registered_address.province_id = props.form.identity.province_id
  props.form.registered_address.regency_id = props.form.identity.regency_id
  props.form.registered_address.district_id = props.form.identity.district_id
  props.form.registered_address.village_id = props.form.identity.village_id
  props.form.registered_address.postal_code = props.form.identity.postal_code

  if (!props.form.registered_address.province_id) return
  await props.npwpRegion.fetchRegencies(props.form.registered_address.province_id)
  if (!props.form.registered_address.regency_id) return
  await props.npwpRegion.fetchDistricts(props.form.registered_address.regency_id)
  if (!props.form.registered_address.district_id) return
  await props.npwpRegion.fetchVillages(props.form.registered_address.district_id)
}

function resetNpwpAddress() {
  props.form.registered_address.address_line = ''
  props.form.registered_address.province_id = null
  props.form.registered_address.regency_id = null
  props.form.registered_address.district_id = null
  props.form.registered_address.village_id = null
  props.form.registered_address.postal_code = ''
  props.npwpRegion.regencies.value = []
  props.npwpRegion.districts.value = []
  props.npwpRegion.villages.value = []
}

function addContact() {
  props.form.contacts.push({ full_name: '', position: '', phone: '', mobile: '', email: '' })
}

// Baris hasil prefill punya id -- backend hanya menghapus kontak yang id-nya ikut dikirim di remove_contact_ids.
function removeContact(idx: number) {
  const id = props.form.contacts[idx]?.id
  if (id !== undefined) props.form.remove_contact_ids.push(id)
  props.form.contacts.splice(idx, 1)
}

watch(npwpSameAsHeadOffice, (checked) => {
  if (checked) copyHeadOfficeToNpwp()
  else resetNpwpAddress()
})
</script>

<template>
  <div class="space-y-6">
    <h2 class="font-header text-xl">Company Information</h2>
    <p class="font-caption">
      Lengkapi identitas dan alamat perusahaan sesuai dokumen resmi (KTP/Akta, NPWP). Data ini akan digunakan sebagai
      acuan proses verifikasi KYC.
    </p>

    <!-- Identity dasar -->
    <div class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
      <div>
        <FormLabel class="font-label !mb-1 block">Full Registered Company Name
          <RequiredAsterisk />
        </FormLabel>
        <FormInput v-model="form.identity.company_name" type="text" placeholder="e.g. PT Contoh Sejahtera Abadi"
          readonly :class="errors['identity.company_name'] ? 'input-error' : ''" />
        <small v-if="errors['identity.company_name']" class="block input-error-text">{{ errors['identity.company_name']
        }}</small>
        <p class="font-caption mt-1">
          Nama perusahaan mengikuti data yang sudah terdaftar, tidak dapat diubah di sini.
        </p>
      </div>

      <div>
        <FormLabel class="font-label !mb-1 block">Holding (if any)</FormLabel>
        <FormInput v-model="form.identity.parent_company" type="text" placeholder="e.g. PT Induk Group (optional)" />
      </div>
    </div>

    <!-- Head Office Address -->
    <div class="rounded-lg bg-white p-6 shadow-sm">
      <div class="font-section mb-1 border-b border-slate-100 pb-2">HEAD OFFICE ADDRESS</div>
      <p class="font-caption mb-3">Alamat kantor pusat yang aktif saat ini beroperasi.</p>

      <div class="space-y-4">
        <div>
          <FormLabel class="font-label !mb-1 block">Address
            <RequiredAsterisk />
          </FormLabel>
          <FormTextarea v-model="form.identity.company_address" rows="3"
            placeholder="Nama jalan, nomor, gedung, lantai, dst."
            :class="errors['identity.company_address'] ? 'input-error' : ''" />
          <small v-if="errors['identity.company_address']" class="block input-error-text">{{
            errors['identity.company_address'] }}</small>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <FormLabel class="font-label !mb-1 block">Province</FormLabel>
            <TomSelect :model-value="form.identity.province_id ?? ''" class="w-full" :options="regionSelectOptions"
              :class="errors['identity.province_id'] ? 'input-error' : ''"
              @update:model-value="onHeadOfficeProvinceChange">
              <option value="">Cari Provinsi</option>
              <option v-for="p in headOfficeRegion.provinces.value" :key="p.id" :value="p.id">{{ p.name }}</option>
            </TomSelect>
            <small v-if="errors['identity.province_id']" class="block input-error-text">{{
              errors['identity.province_id'] }}</small>
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Regency</FormLabel>
            <TomSelect :key="String(!!form.identity.province_id)" :model-value="form.identity.regency_id ?? ''"
              class="w-full" :options="regionSelectOptions" :class="errors['identity.regency_id'] ? 'input-error' : ''"
              @update:model-value="onHeadOfficeRegencyChange">
              <option value="">
                {{ form.identity.province_id ? 'Cari Kabupaten/Kota' : '-- Pilih Provinsi dulu --' }}
              </option>
              <option v-for="r in headOfficeRegion.regencies.value" :key="r.id" :value="r.id">{{ r.name }}</option>
            </TomSelect>
            <small v-if="errors['identity.regency_id']" class="block input-error-text">{{ errors['identity.regency_id']
              }}</small>
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">District</FormLabel>
            <TomSelect :key="String(!!form.identity.regency_id)" :model-value="form.identity.district_id ?? ''"
              class="w-full" :options="regionSelectOptions" @update:model-value="onHeadOfficeDistrictChange">
              <option value="">{{ form.identity.regency_id ? 'Cari Kecamatan' : '-- Pilih Kabupaten/Kota dulu --' }}
              </option>
              <option v-for="d in headOfficeRegion.districts.value" :key="d.id" :value="d.id">{{ d.name }}</option>
            </TomSelect>
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Village</FormLabel>
            <TomSelect :key="String(!!form.identity.district_id)" :model-value="form.identity.village_id ?? ''"
              class="w-full" :options="regionSelectOptions" @update:model-value="onHeadOfficeVillageChange">
              <option value="">{{ form.identity.district_id ? 'Cari Kelurahan/Desa' : '-- Pilih Kecamatan dulu --' }}
              </option>
              <option v-for="v in headOfficeRegion.villages.value" :key="v.id" :value="v.id">{{ v.name }}</option>
            </TomSelect>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <FormLabel class="font-label !mb-1 block">Postal Code</FormLabel>
            <FormInput v-model="form.identity.postal_code" type="text" placeholder="e.g. 12345" />
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Phone</FormLabel>
            <FormInput v-model="form.identity.phone" type="text" placeholder="e.g. 021-1234567" />
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Fax</FormLabel>
            <FormInput v-model="form.identity.fax" type="text" placeholder="e.g. 021-1234568 (optional)" />
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Email</FormLabel>
            <FormInput v-model="form.identity.email" type="email" placeholder="e.g. finance@company.com"
              :class="errors['identity.email'] ? 'input-error' : ''" />
            <small v-if="errors['identity.email']" class="block input-error-text">{{ errors['identity.email']
            }}</small>
          </div>
        </div>

        <div>
          <FormLabel class="font-label !mb-1 block">Website</FormLabel>
          <FormInput v-model="form.identity.website" type="text"
            placeholder="e.g. https://www.company.com (optional)" />
        </div>
      </div>
    </div>

    <!-- NPWP Address -->
    <div class="rounded-lg bg-white p-6 shadow-sm">
      <div class="font-section mb-1 border-b border-slate-100 pb-2">NPWP ADDRESS (REGISTERED)</div>
      <p class="font-caption mb-3">Alamat sesuai yang tertera di kartu/dokumen NPWP perusahaan.</p>

      <FormCheck class="mb-4">
        <FormCheck.Input id="npwp-same-as-head-office" type="checkbox" v-model="npwpSameAsHeadOffice" />
        <FormCheck.Label htmlFor="npwp-same-as-head-office">NPWP address sama dengan Head Office</FormCheck.Label>
      </FormCheck>

      <div v-if="!npwpSameAsHeadOffice" class="space-y-4">
        <div>
          <FormLabel class="font-label !mb-1 block">Address</FormLabel>
          <FormTextarea v-model="form.registered_address.address_line" rows="3" placeholder="Alamat sesuai dokumen NPWP"
            :class="errors['registered_address.address_line'] ? 'input-error' : ''" />
          <small v-if="errors['registered_address.address_line']" class="block input-error-text">{{
            errors['registered_address.address_line'] }}</small>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <FormLabel class="font-label !mb-1 block">Province</FormLabel>
            <TomSelect :model-value="form.registered_address.province_id ?? ''" class="w-full"
              :options="regionSelectOptions" @update:model-value="onNpwpProvinceChange">
              <option value="">Cari Provinsi</option>
              <option v-for="p in npwpRegion.provinces.value" :key="p.id" :value="p.id">{{ p.name }}</option>
            </TomSelect>
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Regency</FormLabel>
            <TomSelect :key="String(!!form.registered_address.province_id)"
              :model-value="form.registered_address.regency_id ?? ''" class="w-full" :options="regionSelectOptions"
              @update:model-value="onNpwpRegencyChange">
              <option value="">
                {{ form.registered_address.province_id ? 'Cari Kabupaten/Kota' : '-- Pilih Provinsi dulu --' }}
              </option>
              <option v-for="r in npwpRegion.regencies.value" :key="r.id" :value="r.id">{{ r.name }}</option>
            </TomSelect>
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">District</FormLabel>
            <TomSelect :key="String(!!form.registered_address.regency_id)"
              :model-value="form.registered_address.district_id ?? ''" class="w-full" :options="regionSelectOptions"
              @update:model-value="onNpwpDistrictChange">
              <option value="">
                {{ form.registered_address.regency_id ? 'Cari Kecamatan' : '-- Pilih Kabupaten/Kota dulu --' }}
              </option>
              <option v-for="d in npwpRegion.districts.value" :key="d.id" :value="d.id">{{ d.name }}</option>
            </TomSelect>
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Village</FormLabel>
            <TomSelect :key="String(!!form.registered_address.district_id)"
              :model-value="form.registered_address.village_id ?? ''" class="w-full" :options="regionSelectOptions"
              @update:model-value="onNpwpVillageChange">
              <option value="">
                {{ form.registered_address.district_id ? 'Cari Kelurahan/Desa' : '-- Pilih Kecamatan dulu --' }}
              </option>
              <option v-for="v in npwpRegion.villages.value" :key="v.id" :value="v.id">{{ v.name }}</option>
            </TomSelect>
          </div>
        </div>

        <div class="max-w-xs">
          <FormLabel class="font-label !mb-1 block">Postal Code</FormLabel>
          <FormInput v-model="form.registered_address.postal_code" type="text" placeholder="e.g. 12345" />
        </div>
      </div>
    </div>

    <!-- Type of Business + Ownership -->
    <div class="grid gap-6 md:grid-cols-3">
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-3 border-b border-slate-100 pb-2">TYPE OF BUSINESS</div>
        <div class="space-y-2">
          <FormCheck v-for="(opt, idx) in typeBusinessOptions" :key="opt">
            <FormCheck.Input :id="'business-type-' + idx" type="radio" :value="opt"
              v-model="form.identity.business_type" />
            <FormCheck.Label :htmlFor="'business-type-' + idx">{{ opt }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="business-type-other" type="radio" value="Other"
              v-model="form.identity.business_type" />
            <FormCheck.Label htmlFor="business-type-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.identity.business_type === 'Other'" v-model="form.identity.business_type_other"
            type="text" placeholder="Specify" />
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-3 border-b border-slate-100 pb-2">OWNERSHIP</div>
        <div class="space-y-2">
          <FormCheck v-for="(opt, idx) in ownershipOptions" :key="opt">
            <FormCheck.Input :id="'ownership-' + idx" type="radio" :value="opt"
              v-model="form.identity.ownership_type" />
            <FormCheck.Label :htmlFor="'ownership-' + idx">{{ opt }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="ownership-other" type="radio" value="Other" v-model="form.identity.ownership_type" />
            <FormCheck.Label htmlFor="ownership-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.identity.ownership_type === 'Other'" v-model="form.identity.ownership_type_other"
            type="text" placeholder="Specify" />
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-3 border-b border-slate-100 pb-2">INCOTERMS</div>
        <div class="space-y-2">
          <FormCheck v-for="(opt, idx) in incoTermsOptions" :key="opt.code">
            <FormCheck.Input :id="'inco-terms-' + idx" type="radio" :value="opt.code"
              v-model="form.identity.inco_terms" />
            <FormCheck.Label :htmlFor="'inco-terms-' + idx">{{ opt.code }} - {{ opt.label }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="inco-terms-other" type="radio" value="Other" v-model="form.identity.inco_terms" />
            <FormCheck.Label htmlFor="inco-terms-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.identity.inco_terms === 'Other'" v-model="form.identity.inco_terms_other" type="text"
            placeholder="Specify" />
        </div>
      </div>
    </div>

    <!-- PIC Invoice -->
    <div class="rounded-lg bg-white p-6 shadow-sm">
      <div class="font-section mb-1 border-b border-slate-100 pb-2">PIC INVOICE (KONTAK FINANCE)</div>
      <div class="mb-3 mt-3 flex items-center justify-between gap-4">
        <p class="font-caption">Kontak yang bisa dihubungi terkait proses invoice dan pembayaran.</p>
        <Button type="button" size="sm" variant="outline-primary" class="inline-flex items-center gap-2"
          @click="addContact">
          <Lucide icon="Plus" class="h-4 w-4" />
          Tambah
        </Button>
      </div>
      <p class="font-caption mt-1">Isi minimal salah satu dari Telepon, Mobile, atau Email.</p>
      <small v-if="errors['contacts']" class="mb-2 block input-error-text">{{ errors['contacts'] }}</small>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full min-w-[880px] divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="w-12 px-3 py-2 font-label text-center">No</th>
              <th class="px-3 py-2 font-label text-left">Name
                <RequiredAsterisk />
              </th>
              <th class="px-3 py-2 font-label text-left">Division/Bagian
                <RequiredAsterisk />
              </th>
              <th class="px-3 py-2 font-label text-left">Phone</th>
              <th class="px-3 py-2 font-label text-left">Mobile</th>
              <th class="px-3 py-2 font-label text-left">Email</th>
              <th class="w-16 px-3 py-2 font-label text-center">Aksi</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="(contact, idx) in form.contacts" :key="idx" class="transition hover:bg-slate-50">
              <td class="px-3 py-2 font-num text-center">{{ idx + 1 }}.</td>
              <td class="px-3 py-2">
                <FormInput v-model="contact.full_name" type="text" placeholder="Nama lengkap PIC Finance"
                  :class="errors[`contacts.${idx}.full_name`] ? 'input-error' : ''" />
                <small v-if="errors[`contacts.${idx}.full_name`]" class="block input-error-text">
                  {{ errors[`contacts.${idx}.full_name`] }}
                </small>
              </td>
              <td class="px-3 py-2">
                <FormInput v-model="contact.position" type="text" placeholder="e.g. Finance Manager"
                  :class="errors[`contacts.${idx}.position`] ? 'input-error' : ''" />
                <small v-if="errors[`contacts.${idx}.position`]" class="block input-error-text">
                  {{ errors[`contacts.${idx}.position`] }}
                </small>
              </td>
              <td class="px-3 py-2">
                <FormInput v-model="contact.phone" type="text" placeholder="e.g. 021-1234567"
                  :class="errors[`contacts.${idx}.phone`] ? 'input-error' : ''" />
                <small v-if="errors[`contacts.${idx}.phone`]" class="block input-error-text">
                  {{ errors[`contacts.${idx}.phone`] }}
                </small>
              </td>
              <td class="px-3 py-2">
                <FormInput v-model="contact.mobile" type="text" placeholder="e.g. 08123456789"
                  :class="errors[`contacts.${idx}.mobile`] ? 'input-error' : ''" />
                <small v-if="errors[`contacts.${idx}.mobile`]" class="block input-error-text">
                  {{ errors[`contacts.${idx}.mobile`] }}
                </small>
              </td>
              <td class="px-3 py-2">
                <FormInput v-model="contact.email" type="email" placeholder="e.g. finance.pic@company.com"
                  :class="errors[`contacts.${idx}.email`] ? 'input-error' : ''" />
                <small v-if="errors[`contacts.${idx}.email`]" class="block input-error-text">
                  {{ errors[`contacts.${idx}.email`] }}
                </small>
              </td>
              <td class="px-3 py-2 text-center">
                <Button type="button" variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none" title="Hapus"
                  :disabled="form.contacts.length === 1" @click="removeContact(idx)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
