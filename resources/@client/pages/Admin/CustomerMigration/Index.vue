<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import { Dialog } from '@/components/Base/Headless'
import { FormLabel } from '@/components/Base/Form'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'

interface UserOption {
  id: number
  name: string
}

interface CustomerRow {
  id_customer: number
  company_name: string
}

const { success, error: notifyError } = useNotification()
const userApi = createResourceApi('/users')

/* State: user pickers */
const users = ref<UserOption[]>([])
const loadingUsers = ref(false)
const selectedFromUserId = ref('')
const selectedToUserId = ref('')

/* State: customer table */
const customersOfFromUser = ref<CustomerRow[]>([])
const loadingCustomers = ref(false)
const selectedCustomerIds = ref<Set<number>>(new Set())

/* State: migrate modal */
const showMigrateModal = ref(false)
const migrating = ref(false)

const fromUser = computed(() =>
  users.value.find(u => u.id === Number(selectedFromUserId.value)) ?? null,
)
const toUser = computed(() =>
  users.value.find(u => u.id === Number(selectedToUserId.value)) ?? null,
)
// Marketing/pemilik baru tidak boleh sama dengan marketing/pemilik lama yang sedang dipilih.
const toUserOptions = computed(() =>
  users.value.filter(u => u.id !== Number(selectedFromUserId.value)),
)

const selectedCount = computed(() => selectedCustomerIds.value.size)
const allSelected = computed(() =>
  customersOfFromUser.value.length > 0 &&
  selectedCustomerIds.value.size === customersOfFromUser.value.length,
)

onMounted(fetchUsers)

// Watcher terpisah dari watcher lain di halaman ini -- ganti "dari" user selalu
// mereset pilihan customer & tabel, supaya tidak ada baris tersisa dari owner sebelumnya.
watch(selectedFromUserId, () => {
  selectedCustomerIds.value.clear()
  customersOfFromUser.value = []

  if (selectedFromUserId.value) {
    fetchCustomersOfFromUser()
  }
})

async function fetchUsers() {
  loadingUsers.value = true

  try {
    const { data } = await userApi.getAll({ as_list: true })
    const list = Array.isArray(data) ? data : (data.data ?? [])
    users.value = list.map((u: any) => ({ id: u.id, name: u.name }))
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat daftar user')
  } finally {
    loadingUsers.value = false
  }
}

async function fetchCustomersOfFromUser() {
  loadingCustomers.value = true

  try {
    const { data } = await axios.get('/api/admin/customers/by-owner', {
      params: { user_id: selectedFromUserId.value },
    })
    customersOfFromUser.value = data.data ?? []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat customer milik marketing ini')
  } finally {
    loadingCustomers.value = false
  }
}

function isChecked(idCustomer: number) {
  return selectedCustomerIds.value.has(idCustomer)
}

function toggleCustomer(idCustomer: number, checked: boolean) {
  if (checked) {
    selectedCustomerIds.value.add(idCustomer)
  } else {
    selectedCustomerIds.value.delete(idCustomer)
  }
}

function toggleSelectAll(checked: boolean) {
  if (checked) {
    customersOfFromUser.value.forEach(c => selectedCustomerIds.value.add(c.id_customer))
  } else {
    selectedCustomerIds.value.clear()
  }
}

function openMigrateModal() {
  if (selectedCount.value === 0) return
  selectedToUserId.value = ''
  showMigrateModal.value = true
}

function closeMigrateModal() {
  if (migrating.value) return
  showMigrateModal.value = false
}

async function confirmMigrate() {
  if (!selectedToUserId.value || selectedCustomerIds.value.size === 0) return

  migrating.value = true

  try {
    const { data } = await axios.post('/api/admin/customers/migrate-ownership', {
      customer_ids: Array.from(selectedCustomerIds.value),
      to_user_id: Number(selectedToUserId.value),
    })

    success(
      'Berhasil',
      `${data.migrated_count} customer berhasil dipindahkan ke ${toUser.value?.name ?? 'marketing baru'}.`,
    )

    showMigrateModal.value = false
    selectedCustomerIds.value.clear()
    await fetchCustomersOfFromUser()
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memigrasi kepemilikan customer')
  } finally {
    migrating.value = false
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">

      <PageHeader title="Migrasi Kepemilikan Customer"
        description="Pindahkan kepemilikan customer dari satu marketing/pemilik ke marketing/pemilik lain, bisa sekaligus banyak customer." />

      <CardSection title="Pilih Marketing/Pemilik Lama" icon="UserCog"
        description="Cari marketing/pemilik yang customer-nya ingin dipindahkan.">
        <div class="max-w-md">
          <FormLabel for="from-user">Marketing/Pemilik Lama</FormLabel>
          <TomSelect id="from-user" v-model="selectedFromUserId" class="w-full"
            :options="{ placeholder: 'Cari marketing/pemilik...', dropdownParent: 'body' }">
            <option value="">-- Pilih marketing/pemilik --</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
          </TomSelect>
          <small v-if="loadingUsers" class="font-caption mt-1 block text-slate-400">Memuat daftar user...</small>
        </div>
      </CardSection>

      <CardSection v-if="selectedFromUserId" title="Customer Milik Marketing Ini" icon="Building2"
        :description="`Customer aktif yang saat ini dimiliki oleh ${fromUser?.name ?? '-'}.`"
        content-class="!px-0 !pb-0">
        <template #action>
          <Button variant="primary" class="inline-flex items-center gap-2" :disabled="selectedCount === 0"
            @click="openMigrateModal">
            <Lucide icon="ArrowRightLeft" class="h-4 w-4" />
            Migrasi ke Marketing Lain
            <span v-if="selectedCount > 0" class="font-num">({{ selectedCount }})</span>
          </Button>
        </template>

        <div class="overflow-x-auto border-t border-slate-200">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="w-12 px-4 py-3 text-center">
                  <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300"
                    :checked="allSelected" :disabled="customersOfFromUser.length === 0"
                    @change="toggleSelectAll(($event.target as HTMLInputElement).checked)" />
                </th>
                <th class="px-4 py-3 font-label text-left">Nama Perusahaan</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-if="loadingCustomers">
                <td colspan="2" class="px-4 py-8 text-center font-body">
                  Memuat customer...
                </td>
              </tr>

              <tr v-else-if="customersOfFromUser.length === 0">
                <td colspan="2" class="px-4 py-8 text-center font-body">
                  Marketing/pemilik ini tidak memiliki customer aktif.
                </td>
              </tr>

              <tr v-for="c in customersOfFromUser" :key="c.id_customer" class="transition hover:bg-slate-50">
                <td class="px-4 py-3 text-center">
                  <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300"
                    :checked="isChecked(c.id_customer)"
                    @change="toggleCustomer(c.id_customer, ($event.target as HTMLInputElement).checked)" />
                </td>
                <td class="px-4 py-3 font-body">{{ c.company_name }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardSection>

    </div>

    <Dialog :open="showMigrateModal" size="md" @close="closeMigrateModal">
      <Dialog.Panel>
        <div class="p-6">
          <h3 class="font-header">Migrasi ke Marketing Lain</h3>
          <p class="font-body mt-1">
            Pilih marketing/pemilik baru untuk customer yang sudah dipilih.
          </p>

          <div class="mt-4">
            <FormLabel for="to-user">Marketing/Pemilik Baru</FormLabel>
            <TomSelect id="to-user" v-model="selectedToUserId" class="w-full"
              :options="{ placeholder: 'Cari marketing/pemilik...', dropdownParent: 'body' }">
              <option value="">-- Pilih marketing/pemilik baru --</option>
              <option v-for="u in toUserOptions" :key="u.id" :value="u.id">{{ u.name }}</option>
            </TomSelect>
          </div>

          <div class="mt-4 rounded-md bg-slate-50 p-3 font-body">
            <span class="font-num font-semibold">{{ selectedCount }}</span>
            customer akan dipindahkan dari
            <span class="font-semibold">{{ fromUser?.name ?? '-' }}</span>
            ke
            <span class="font-semibold">{{ toUser?.name ?? '-' }}</span>.
          </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
          <Button variant="outline-secondary" :disabled="migrating" @click="closeMigrateModal">Batal</Button>
          <Button variant="primary" class="inline-flex items-center gap-2" :disabled="!selectedToUserId || migrating"
            @click="confirmMigrate">
            <Lucide v-if="migrating" icon="Loader" class="h-4 w-4 animate-spin" />
            Konfirmasi
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  </div>
</template>
