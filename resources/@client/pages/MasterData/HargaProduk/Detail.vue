<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi.js'
import { formatDate } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const { error } = useNotification()
const hargaProdukApi = createResourceApi('/produk-hargas')
const id = route.params.id

const loading = ref(false)
const detail = ref<any>(null)

async function fetchDetail() {
  loading.value = true

  try {
    const { data } = await hargaProdukApi.getById(id as string)
    detail.value = data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal memuat detail data')
    router.push({ name: 'produk-hargas' })
  } finally {
    loading.value = false
  }
}

function formatNumber(value: number | string | null = 0) {
  if (value === null || value === undefined || value === '') return '-'
  const num = typeof value === 'string' ? Number(value) : value
  return isNaN(num) ? '-' : Number(num).toLocaleString('id-ID')
}

onMounted(() => {
  fetchDetail()
})
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="font-display">Detail Harga Produk</h2>
        <p class="font-body mt-1">
          Informasi lengkap harga produk termasuk BM, OM, dan CEO.
        </p>
      </div>

      <div class="flex gap-2">
        <Button variant="outline-secondary" @click="router.push({ name: 'produk-hargas' })">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>

        <Button variant="primary" v-if="detail"
          @click="router.push({ name: 'produk-hargas-edit', params: { id: detail.id_produk_harga } })">
          <Lucide icon="Edit" class="mr-2 h-4 w-4" />
          Edit
        </Button>
      </div>
    </div>

    <div v-if="loading" class="rounded-lg border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
      Memuat detail...
    </div>

    <div v-else-if="detail" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <!-- LEFT -->
      <div class="xl:col-span-2 space-y-6">
        <CardSection title="Informasi Utama" description="Periode, cabang, dan data produk" icon="FileText">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <div class="font-label">Periode Awal</div>
              <div class="font-strong mt-1">{{ formatDate(detail.periode_awal) }}</div>
            </div>

            <div>
              <div class="font-label">Periode Akhir</div>
              <div class="font-strong mt-1">{{ formatDate(detail.periode_akhir) }}</div>
            </div>

            <div>
              <div class="font-label">Cabang</div>
              <div class="font-strong mt-1">{{ detail.cabang?.nama_cabang || '-' }}</div>
            </div>

            <div>
              <div class="font-label">Produk</div>
              <div class="font-strong mt-1">
                {{ detail.produk?.nama_produk || '-' }}
              </div>
            </div>

            <div>
              <div class="font-label">Ukuran</div>
              <div class="font-strong mt-1">
                {{ detail.produk?.ukuran?.nama_ukuran || '-' }}
              </div>
            </div>

            <div>
              <div class="font-label">Satuan</div>
              <div class="font-strong mt-1">
                {{ detail.produk?.ukuran?.satuan?.nama_satuan || '-' }}
              </div>
            </div>
          </div>
        </CardSection>

        <CardSection title="Harga Utama" description="Harga referensi utama untuk produk" icon="Tags"
          icon-class="bg-indigo-100 text-indigo-600">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4">
              <div class="font-label">COGS</div>
              <div class="font-num-lg mt-2">{{ formatNumber(detail.harga_cogs) }}</div>
            </div>

            <div class="rounded-xl bg-slate-50 p-4">
              <div class="font-label">Margin</div>
              <div class="font-num-lg mt-2">{{ formatNumber(detail.harga_margin) }}</div>
            </div>

            <div class="rounded-xl bg-blue-50 p-4">
              <div class="font-label !text-blue-400">Price List TDS</div>
              <div class="font-num-lg mt-2 !text-blue-700">{{ formatNumber(detail.harga_price_list) }}</div>
            </div>

            <div class="rounded-xl bg-emerald-50 p-4">
              <div class="font-label !text-emerald-400">Price List PE</div>
              <div class="font-num-lg mt-2 !text-emerald-700">{{ formatNumber(detail.harga_price_list_pe) }}</div>
            </div>
          </div>
        </CardSection>

        <CardSection title="Catatan" description="Catatan tambahan untuk harga produk" icon="StickyNote"
          icon-class="bg-amber-100 text-amber-600">
          <p class="font-body leading-6">
            {{ detail.catatan || '-' }}
          </p>
        </CardSection>
      </div>

      <!-- RIGHT -->
      <div class="space-y-6">
        <CardSection title="Harga Approval" description="Harga berdasarkan level approval" icon="ShieldCheck"
          icon-class="bg-emerald-100 text-emerald-600">
          <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 p-4">
              <div class="font-label">Harga BM</div>
              <div class="font-num-lg mt-2">{{ formatNumber(detail.harga_bm) }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
              <div class="font-label">Harga OM</div>
              <div class="font-num-lg mt-2">{{ formatNumber(detail.harga_om) }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
              <div class="font-label">Harga CEO</div>
              <div class="font-num-lg mt-2">{{ formatNumber(detail.harga_ceo) }}</div>
            </div>
          </div>
        </CardSection>

        <CardSection title="Metadata" description="Jejak pembuatan dan perubahan data" icon="History"
          icon-class="bg-slate-100 text-slate-600">
          <div class="space-y-3 font-body">
            <div>
              <span class="text-slate-400">Created By:</span>
              <div class="font-strong">{{ detail.created_by || '-' }}</div>
            </div>

            <div>
              <span class="text-slate-400">Created Time:</span>
              <div class="font-strong">{{ detail.created_time || '-' }}</div>
            </div>

            <div>
              <span class="text-slate-400">Last Update By:</span>
              <div class="font-strong">{{ detail.lastupdate_by || '-' }}</div>
            </div>

            <div>
              <span class="text-slate-400">Last Update Time:</span>
              <div class="font-strong">{{ detail.lastupdate_time || '-' }}</div>
            </div>
          </div>
        </CardSection>
      </div>
    </div>
    </div>
  </div>
</template>
