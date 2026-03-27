<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRoute, useRouter } from 'vue-router'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'

const route = useRoute()
const router = useRouter()
const id = route.params.id

const loading = ref(false)
const detail = ref<any>(null)

async function fetchDetail() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/produk-hargas/${id}`)
    detail.value = data
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat detail data', 'error')
    router.push({ name: 'produk-hargas' })
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
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
  <div class="p-6 intro-y">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">Detail Harga Produk</h2>
        <p class="mt-1 text-sm text-slate-500">
          Informasi lengkap harga produk termasuk BM, OM, dan CEO.
        </p>
      </div>

      <div class="flex gap-2">
        <Button variant="outline-secondary" @click="router.push({ name: 'produk-hargas' })">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>

        <Button
          variant="primary"
          v-if="detail"
          @click="router.push({ name: 'produk-hargas-edit', params: { id: detail.id_produk_harga } })"
        >
          <Lucide icon="Edit" class="mr-2 h-4 w-4" />
          Edit
        </Button>
      </div>
    </div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500 shadow-sm">
      Memuat detail...
    </div>

    <div v-else-if="detail" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <!-- LEFT -->
      <div class="xl:col-span-2 space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-4 text-lg font-semibold text-slate-800">Informasi Utama</h3>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-400">Periode Awal</div>
              <div class="mt-1 text-sm font-medium text-slate-700">{{ formatDate(detail.periode_awal) }}</div>
            </div>

            <div>
              <div class="text-xs uppercase tracking-wide text-slate-400">Periode Akhir</div>
              <div class="mt-1 text-sm font-medium text-slate-700">{{ formatDate(detail.periode_akhir) }}</div>
            </div>

            <div>
              <div class="text-xs uppercase tracking-wide text-slate-400">Cabang</div>
              <div class="mt-1 text-sm font-medium text-slate-700">{{ detail.cabang?.nama_cabang || '-' }}</div>
            </div>

            <div>
              <div class="text-xs uppercase tracking-wide text-slate-400">Produk</div>
              <div class="mt-1 text-sm font-medium text-slate-700">
                {{ detail.produk?.nama_produk || '-' }}
              </div>
            </div>

            <div>
              <div class="text-xs uppercase tracking-wide text-slate-400">Ukuran</div>
              <div class="mt-1 text-sm font-medium text-slate-700">
                {{ detail.produk?.ukuran?.nama_ukuran || '-' }}
              </div>
            </div>

            <div>
              <div class="text-xs uppercase tracking-wide text-slate-400">Satuan</div>
              <div class="mt-1 text-sm font-medium text-slate-700">
                {{ detail.produk?.ukuran?.satuan?.nama_satuan || '-' }}
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-4 text-lg font-semibold text-slate-800">Harga Utama</h3>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-wide text-slate-400">COGS</div>
              <div class="mt-2 text-lg font-bold text-slate-800">{{ formatNumber(detail.harga_cogs) }}</div>
            </div>

            <div class="rounded-xl bg-slate-50 p-4">
              <div class="text-xs uppercase tracking-wide text-slate-400">Margin</div>
              <div class="mt-2 text-lg font-bold text-slate-800">{{ formatNumber(detail.harga_margin) }}</div>
            </div>

            <div class="rounded-xl bg-blue-50 p-4">
              <div class="text-xs uppercase tracking-wide text-blue-400">Price List TDS</div>
              <div class="mt-2 text-lg font-bold text-blue-700">{{ formatNumber(detail.harga_price_list) }}</div>
            </div>

            <div class="rounded-xl bg-emerald-50 p-4">
              <div class="text-xs uppercase tracking-wide text-emerald-400">Price List PE</div>
              <div class="mt-2 text-lg font-bold text-emerald-700">{{ formatNumber(detail.harga_price_list_pe) }}</div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-4 text-lg font-semibold text-slate-800">Catatan</h3>
          <p class="text-sm leading-6 text-slate-600">
            {{ detail.catatan || '-' }}
          </p>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-4 text-lg font-semibold text-slate-800">Harga Approval</h3>

          <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 p-4">
              <div class="text-xs uppercase tracking-wide text-slate-400">Harga BM</div>
              <div class="mt-2 text-lg font-bold text-slate-800">{{ formatNumber(detail.harga_bm) }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
              <div class="text-xs uppercase tracking-wide text-slate-400">Harga OM</div>
              <div class="mt-2 text-lg font-bold text-slate-800">{{ formatNumber(detail.harga_om) }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
              <div class="text-xs uppercase tracking-wide text-slate-400">Harga CEO</div>
              <div class="mt-2 text-lg font-bold text-slate-800">{{ formatNumber(detail.harga_ceo) }}</div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-4 text-lg font-semibold text-slate-800">Metadata</h3>

          <div class="space-y-3 text-sm">
            <div>
              <span class="text-slate-400">Created By:</span>
              <div class="font-medium text-slate-700">{{ detail.created_by || '-' }}</div>
            </div>

            <div>
              <span class="text-slate-400">Created Time:</span>
              <div class="font-medium text-slate-700">{{ detail.created_time || '-' }}</div>
            </div>

            <div>
              <span class="text-slate-400">Last Update By:</span>
              <div class="font-medium text-slate-700">{{ detail.lastupdate_by || '-' }}</div>
            </div>

            <div>
              <span class="text-slate-400">Last Update Time:</span>
              <div class="font-medium text-slate-700">{{ detail.lastupdate_time || '-' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>