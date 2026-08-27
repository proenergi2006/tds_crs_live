<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, maxLength, required } from '@vuelidate/validators'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormLabel, FormSelect, FormSwitch } from '@/components/Base/Form'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'

type StepRow = {
  step_order: number
  step_name: string
  id_role: number | string
}

type RoleOption = {
  id: number
  name: string
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()
const approvalTemplateApi = createResourceApi('/approval-templates')
const roleApi = createResourceApi('/roles')

const templateId = computed(() => Number(route.params.id || 0))
const mode = computed<'create' | 'edit'>(() => templateId.value ? 'edit' : 'create')

const loading = ref(false)
const pageLoading = ref(false)
const formError = ref<string | null>(null)

const roles = ref<RoleOption[]>([])

const form = reactive({
  code: '',
  name: '',
  is_active: true,
})

const serverErrors = reactive({
  code: '',
  name: '',
})

const rows = ref<StepRow[]>([])

const pageTitle = computed(() => mode.value === 'create' ? 'Tambah Approval Template' : 'Edit Approval Template')
const pageDescription = computed(() =>
  mode.value === 'create'
    ? 'Buat template approval baru beserta urutan step dan role penanggung jawabnya.'
    : 'Perbarui template approval, urutan step, dan role penanggung jawab tiap step.',
)
const submitText = computed(() => mode.value === 'create' ? 'Simpan Template' : 'Simpan Perubahan')

const rules = {
  code: {
    required: helpers.withMessage('Kode template wajib diisi', required),
    maxLength: helpers.withMessage('Kode maksimal 100 karakter', maxLength(100)),
  },
  name: {
    required: helpers.withMessage('Nama template wajib diisi', required),
  },
}

const v$ = useVuelidate(rules, form)

onMounted(async () => {
  await initForm()
})

async function initForm() {
  pageLoading.value = true

  try {
    await fetchRoles()

    if (mode.value === 'edit') {
      await fetchTemplate()
    }
  } finally {
    pageLoading.value = false
  }
}

async function fetchRoles() {
  try {
    const { data } = await roleApi.getAll({ as_list: true })
    roles.value = Array.isArray(data) ? data : (data.data ?? [])
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data role')
  }
}

async function fetchTemplate() {
  try {
    const { data } = await approvalTemplateApi.getById(templateId.value)

    form.code = data.code || ''
    form.name = data.name || ''
    form.is_active = !!data.is_active

    rows.value = (data.steps ?? []).map((step: any) => ({
      step_order: step.step_order,
      step_name: step.step_name || '',
      id_role: step.id_role ?? '',
    }))
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data approval template')
    router.push({ name: 'approval-templates' })
  }
}

// step_order slot tetap, gak pernah digeser -- naik/turun cuma tuker isi slot, hapus slot yang udah kepake historis bisa kena 409
function nextStepOrder() {
  if (!rows.value.length) return 1
  return Math.max(...rows.value.map(r => r.step_order)) + 1
}

function addStep() {
  rows.value.push({ step_order: nextStepOrder(), step_name: '', id_role: '' })
}

function removeStep(index: number) {
  if (rows.value.length <= 1) return
  rows.value.splice(index, 1)
}

function moveStepUp(index: number) {
  if (index <= 0) return
  swapStepContent(index, index - 1)
}

function moveStepDown(index: number) {
  if (index >= rows.value.length - 1) return
  swapStepContent(index, index + 1)
}

function swapStepContent(a: number, b: number) {
  const rowA = rows.value[a]
  const rowB = rows.value[b]

  const tempName = rowA.step_name
  const tempRole = rowA.id_role

  rowA.step_name = rowB.step_name
  rowA.id_role = rowB.id_role

  rowB.step_name = tempName
  rowB.id_role = tempRole
}

function isRoleMissing(idRole: number | string) {
  if (!idRole) return false
  return !roles.value.some(r => r.id === Number(idRole))
}

function getFieldError(field: keyof typeof serverErrors) {
  return serverErrors[field] || v$.value[field].$errors[0]?.$message?.toString() || ''
}

function resetErrors() {
  formError.value = null
  v$.value.$reset()
  Object.assign(serverErrors, { code: '', name: '' })
}

function validateSteps(): string | null {
  if (rows.value.length === 0) {
    return 'Minimal harus ada 1 step approval.'
  }

  const hasEmptyField = rows.value.some(row => !row.step_name.trim() || !row.id_role)
  if (hasEmptyField) {
    return 'Nama step dan role wajib diisi untuk setiap baris.'
  }

  const orders = rows.value.map(row => row.step_order)
  const hasDuplicateOrder = new Set(orders).size !== orders.length
  if (hasDuplicateOrder) {
    return 'step_order tidak boleh duplikat dalam satu template.'
  }

  return null
}

function buildPayload() {
  return {
    code: form.code,
    name: form.name,
    is_active: form.is_active,
    steps: rows.value.map(row => ({
      step_order: row.step_order,
      step_name: row.step_name,
      id_role: Number(row.id_role),
    })),
  }
}

async function submitForm() {
  resetErrors()

  const isValid = await v$.value.$validate()
  const stepsError = validateSteps()

  if (!isValid || stepsError) {
    notifyError('Gagal', stepsError ?? 'Periksa kembali data yang wajib diisi')
    return
  }

  loading.value = true

  try {
    const payload = buildPayload()

    if (mode.value === 'create') {
      await approvalTemplateApi.store(payload)
    } else {
      await approvalTemplateApi.update(templateId.value, payload)
    }

    success(
      'Berhasil',
      mode.value === 'create'
        ? 'Approval template berhasil ditambahkan'
        : 'Approval template berhasil diperbarui',
    )
  } catch (e: any) {
    const status = e.response?.status
    const errors = e.response?.data?.errors

    if (status === 422 && errors) {
      Object.entries(errors).forEach(([key, value]: [string, any]) => {
        if (key in serverErrors) {
          serverErrors[key as keyof typeof serverErrors] = value?.[0] || ''
        }
      })
      formError.value = Object.values(errors).map((value: any) => value?.[0]).filter(Boolean).join('\n')
    } else if (status === 409) {
      // Conflict: step removal diblokir karena sudah dipakai di document_approval_steps historis.
      formError.value = e.response?.data?.message ?? 'Perubahan step ditolak karena sudah dipakai di riwayat approval.'
    } else {
      formError.value = e.response?.data?.message ?? 'Terjadi kesalahan saat menyimpan data'
    }

    notifyError('Gagal menyimpan', formError.value ?? undefined)
  } finally {
    loading.value = false
  }
}

function cancel() {
  if (loading.value) return
  router.push({ name: 'approval-templates' })
}
</script>

<template>
  <FormPage :title="pageTitle" :description="pageDescription" size="lg" :loading="loading || pageLoading"
    :error="formError" :submit-text="submitText" submit-icon="Save" @cancel="cancel" @submit="submitForm">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </template>

    <div class="space-y-5">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <FormLabel htmlFor="template-code">Kode Template
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="template-code" v-model="form.code" placeholder="mis. customer_verification"
            :class="getFieldError('code') ? 'border-rose-500' : ''" />
          <small v-if="getFieldError('code')" class="font-caption !text-rose-600">{{ getFieldError('code') }}</small>
        </div>

        <div>
          <FormLabel htmlFor="template-name">Nama Template
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="template-name" v-model="form.name" placeholder="mis. Verifikasi Data Customer"
            :class="getFieldError('name') ? 'border-rose-500' : ''" />
          <small v-if="getFieldError('name')" class="font-caption !text-rose-600">{{ getFieldError('name') }}</small>
        </div>
      </div>

      <div>
        <FormLabel htmlFor="template-status">Status</FormLabel>
        <div class="mt-2 flex items-center gap-3">
          <FormSwitch>
            <FormSwitch.Input id="template-status" v-model="form.is_active" type="checkbox" />
          </FormSwitch>
          <span class="font-body">
            {{ form.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 class="font-header">Urutan Step Approval</h3>
          <p class="font-body mt-1">
            Urutan baris di bawah ini menentukan urutan step approval. Gunakan tombol panah untuk mengubah urutan.
          </p>
        </div>

        <Button type="button" variant="outline-primary" class="inline-flex items-center gap-2" @click="addStep">
          <Lucide icon="Plus" class="h-4 w-4" />
          Tambah Step
        </Button>
      </div>

      <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="w-16 px-4 py-3 font-label text-center">Urutan</th>
              <th class="px-4 py-3 font-label text-left">
                Nama Step
                <RequiredAsterisk />
              </th>
              <th class="px-4 py-3 font-label text-left">
                Role
                <RequiredAsterisk />
              </th>
              <th class="w-32 px-4 py-3 font-label text-center">Aksi</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-if="rows.length === 0">
              <td colspan="4" class="px-4 py-8 text-center font-body">
                Belum ada step. Klik "Tambah Step" untuk menambahkan.
              </td>
            </tr>

            <tr v-for="(row, index) in rows" :key="`${row.step_order}`" class="transition hover:bg-slate-50">
              <td class="px-4 py-3 text-center align-top">
                <span class="font-num inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100">
                  {{ index + 1 }}
                </span>
              </td>

              <td class="px-4 py-3 align-top">
                <FormInput v-model="row.step_name" class="min-w-[220px]" placeholder="mis. Admin Finance" />
              </td>

              <td class="px-4 py-3 align-top">
                <FormSelect v-model="row.id_role" class="min-w-[200px]">
                  <option disabled value="">-- Pilih Role --</option>
                  <option v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </FormSelect>
                <small v-if="isRoleMissing(row.id_role)" class="font-caption !text-rose-600">
                  Role tidak ditemukan
                </small>
              </td>

              <td class="px-4 py-3 align-top">
                <div class="flex items-center justify-center gap-1.5">
                  <Button type="button" variant="outline-secondary" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                    title="Naikkan urutan" :disabled="index === 0" @click="moveStepUp(index)">
                    <Lucide icon="ArrowUp" class="h-4 w-4" />
                  </Button>
                  <Button type="button" variant="outline-secondary" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                    title="Turunkan urutan" :disabled="index === rows.length - 1" @click="moveStepDown(index)">
                    <Lucide icon="ArrowDown" class="h-4 w-4" />
                  </Button>
                  <Button type="button" variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Hapus"
                    :disabled="rows.length <= 1" @click="removeStep(index)">
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </FormPage>
</template>
