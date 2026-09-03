<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'

import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import { FormInput, FormLabel } from '@/components/Base/Form'
import SearchBox from '@/components/SystemDesign/Form/SearchBox.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'

interface DraftItem {
  id_produk: string
  source_branch_id: string
  product_price_id: number | null
  harga_price_list: number
  source_name: string
  persen: number
  volume_order: string
}

const props = withDefaults(
  defineProps<{
    open: boolean
    pricePeriodId: string | number | null
    periodLabel?: string
    cabangs: any[]
    produks: any[]
    initialItems?: any[]
    usePe?: boolean
  }>(),
  { periodLabel: '', initialItems: () => [], usePe: false },
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'save', items: DraftItem[]): void
}>()

const sourceBranchIds = ref<string[]>([])
const rows = ref<any[]>([])
const loadingRows = ref(false)
const search = ref('')
const draft = ref<DraftItem[]>([])

function toFloat(v: string | number): number {
  if (typeof v === 'number') return v
  const n = parseFloat((v || '').toString().replace(/\./g, '').replace(',', '.'))
  return isNaN(n) ? 0 : n
}

function formatCurrency(v: number | string = 0): string {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return !isNaN(n) ? `Rp. ${n.toLocaleString('id-ID')}` : '-'
}

function branchName(id: string | number): string {
  return props.cabangs.find(c => String(c.id_cabang) === String(id))?.nama_cabang || `#${id}`
}

function rowPrice(r: any): number {
  const pe = Number(r.price_list_pe ?? 0)
  const pl = Number(r.price_list ?? 0)
  return props.usePe && pe > 0 ? pe : pl
}

function volNum(d: DraftItem): number {
  return parseInt((d.volume_order || '').replace(/\./g, ''), 10) || 0
}

function persenNum(d: DraftItem): number {
  return parseInt(String(d.persen ?? '').replace(/[^\d]/g, ''), 10) || 0
}

const totalVolumeInput = ref('')
const totalVolNum = computed(() => parseInt(totalVolumeInput.value.replace(/\./g, ''), 10) || 0)

function recomputeVolumes() {
  const total = totalVolNum.value
  const sumP = draft.value.reduce((s, d) => s + persenNum(d), 0)
  if (total <= 0 || sumP <= 0) {
    draft.value.forEach(d => { d.volume_order = '0' })
    return
  }
  const lastIdx = draft.value.length - 1
  draft.value.forEach((d, i) => {
    if (i === lastIdx) return
    d.volume_order = Math.round(total * persenNum(d) / 100).toLocaleString('id-ID')
  })
  const others = draft.value.reduce((s, d, i) => (i === lastIdx ? s : s + volNum(d)), 0)
  draft.value[lastIdx].volume_order = Math.max(0, total - others).toLocaleString('id-ID')
}

function onPersen(idx: number, e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  const num = parseInt(raw, 10)
  draft.value[idx].persen = isNaN(num) ? 0 : num
  recomputeVolumes()
}

function onTotalVolume(e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  const num = parseInt(raw, 10)
  totalVolumeInput.value = isNaN(num) ? '' : num.toLocaleString('id-ID')
  recomputeVolumes()
}

async function fetchRows() {
  rows.value = []
  if (!props.pricePeriodId || props.pricePeriodId === 'custom') return
  loadingRows.value = true
  try {
    const { data } = await axios.get('/api/product-prices', {
      params: { price_period_id: props.pricePeriodId, as_list: 1 },
    })
    rows.value = data.data || data || []
  } catch {
    rows.value = []
  } finally {
    loadingRows.value = false
  }
}

watch(
  () => [props.open, props.pricePeriodId],
  () => { if (props.open) fetchRows() },
  { immediate: true },
)

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return
    draft.value = (props.initialItems || []).map(x => ({
      id_produk: String(x.id_produk ?? ''),
      source_branch_id: String(x.source_branch_id ?? ''),
      product_price_id: x.product_price_id ?? null,
      harga_price_list: Number(x.harga_price_list ?? 0),
      source_name: x.source_name || (x.source_branch_id ? branchName(x.source_branch_id) : ''),
      persen: Number(x.persen ?? 0),
      volume_order: x.volume_order ?? '',
    }))

    const fromDraft = [...new Set(draft.value.map(d => d.source_branch_id).filter(Boolean))]
    if (fromDraft.length) {
      sourceBranchIds.value = fromDraft
    } else {
      const jkt = props.cabangs.find(c => String(c.nama_cabang ?? '').toLowerCase().includes('jakarta'))
      const first = jkt || props.cabangs[0]
      sourceBranchIds.value = first ? [String(first.id_cabang)] : []
    }

    totalVolumeInput.value = draft.value.reduce((s, d) => s + volNum(d), 0).toLocaleString('id-ID')
    sortDraft()
    recomputeVolumes()
  },
  { immediate: true },
)

const produkById = computed<Record<string, any>>(() => {
  const m: Record<string, any> = {}
  for (const p of props.produks) m[String(p.id_produk)] = p
  return m
})

function isAdded(productId: number | string, branchId: number | string): boolean {
  return draft.value.some(
    d => d.id_produk === String(productId) && d.source_branch_id === String(branchId),
  )
}

const referenceRows = computed(() => {
  const q = search.value.trim().toLowerCase()
  const allow = new Set(sourceBranchIds.value.map(String))
  return rows.value
    .filter(r => allow.has(String(r.branch_id)))
    .map(r => {
      const p = produkById.value[String(r.product_id)]
      const ukuran = p?.ukuran?.nama_ukuran
        ? `${p.ukuran.nama_ukuran}${p.ukuran?.satuan?.nama_satuan ? ' ' + p.ukuran.satuan.nama_satuan : ''}`
        : ''
      return {
        key: r.id,
        product_id: r.product_id,
        branch_id: r.branch_id,
        product_price_id: r.id,
        nama: (p?.nama_produk || r.product?.nama_produk || `#${r.product_id}`) as string,
        jenis: (p?.jenis?.nama || '') as string,
        ukuran,
        source: branchName(r.branch_id),
        harga: rowPrice(r),
        added: isAdded(r.product_id, r.branch_id),
      }
    })
    .filter(r => !q || r.nama.toLowerCase().includes(q) || r.jenis.toLowerCase().includes(q))
    .sort((a, b) => a.nama.localeCompare(b.nama) || a.source.localeCompare(b.source))
})

function addFromRef(ref: any) {
  if (ref.added) return
  draft.value.push({
    id_produk: String(ref.product_id),
    source_branch_id: String(ref.branch_id),
    product_price_id: ref.product_price_id ?? null,
    harga_price_list: ref.harga,
    source_name: ref.source,
    persen: 0,
    volume_order: '0',
  })
  sortDraft()
  recomputeVolumes()
}

function removeDraft(idx: number) {
  draft.value.splice(idx, 1)
  sortDraft()
  recomputeVolumes()
}

function draftProdukLabel(id: string): string {
  const p = produkById.value[String(id)]
  if (!p) return `#${id}`
  const uk = p.ukuran?.nama_ukuran ? ` · ${p.ukuran.nama_ukuran}${p.ukuran?.satuan?.nama_satuan ? ' ' + p.ukuran.satuan.nama_satuan : ''}` : ''
  return `${p.nama_produk}${uk}`
}

// sengaja gak dipanggil dari onPersen/onTotalVolume — nyortir sambil user ngetik mindahin row, fokus input kelempar
function sortDraft() {
  draft.value.sort((a, b) => {
    const pa = produkById.value[String(a.id_produk)] || {}
    const pb = produkById.value[String(b.id_produk)] || {}
    return (
      String(pa.nama_produk ?? '').localeCompare(String(pb.nama_produk ?? ''), 'id') ||
      String(pa.ukuran?.nama_ukuran ?? '').localeCompare(String(pb.ukuran?.nama_ukuran ?? ''), 'id') ||
      String(a.source_name ?? '').localeCompare(String(b.source_name ?? ''), 'id')
    )
  })
}

const totalPersen = computed(() =>
  Math.round(draft.value.reduce((s, d) => s + toFloat(d.persen), 0) * 100) / 100,
)
const totalVolume = computed(() =>
  draft.value.reduce((s, d) => s + (parseInt((d.volume_order || '').replace(/\./g, ''), 10) || 0), 0),
)
const persenOk = computed(() => draft.value.length === 0 || totalPersen.value === 100)
const canSave = computed(() =>
  draft.value.length > 0 &&
  totalVolNum.value > 0 &&
  Math.round(draft.value.reduce((s, d) => s + persenNum(d), 0)) === 100 &&
  draft.value.every(d => d.id_produk && d.source_branch_id),
)

function save() {
  emit('save', draft.value.map(d => ({ ...d })))
}
</script>

<template>
  <FormModal :open="open" size="xxl" title="Susun Item Penawaran"
    :description="periodLabel ? `Periode: ${periodLabel}` : 'Pilih baris harga di kanan, tambahkan ke daftar item di kiri.'"
    submit-text="Simpan Item" submit-icon="Save" :submit-disabled="!canSave" @close="emit('close')" @submit="save">

    <div class="gap-5 grid grid-cols-1 lg:grid-cols-2">

      <div class="min-w-0">
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-strong">Item Penawaran ({{ draft.length }})</h3>
          <span class="font-caption" :class="persenOk ? '!text-slate-500' : '!text-rose-600'">
            Total rasio {{ totalPersen }}% · {{ totalVolume.toLocaleString('id-ID') }} vol
          </span>
        </div>

        <div class="mb-3">
          <FormLabel>Total Volume Order</FormLabel>
          <FormInput v-model="totalVolumeInput" type="text" inputmode="numeric" placeholder="0"
            class="text-right" @input="onTotalVolume($event)" />
        </div>

        <div class="border border-slate-200 rounded-xl overflow-hidden">
          <table class="divide-y divide-slate-200 w-full">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-3 py-2 font-label text-left">Produk / Source</th>
                <th class="px-2 py-2 w-20 font-label text-right">Persen</th>
                <th class="px-2 py-2 w-32 font-label text-right">Volume</th>
                <th class="px-2 py-2 w-10"></th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="(d, idx) in draft" :key="d.id_produk + '-' + d.source_branch_id">
                <td class="px-3 py-2">
                  <div class="font-strong leading-snug">{{ draftProdukLabel(d.id_produk) }}</div>
                  <div class="font-caption">
                    {{ d.source_name }} · {{ formatCurrency(d.harga_price_list) }}
                  </div>
                </td>
                <td class="px-2 py-2">
                  <FormInput :model-value="String(d.persen)" type="text" inputmode="numeric"
                    class="text-right" @input="onPersen(idx, $event)" />
                </td>
                <td class="px-2 py-2 font-num text-right">{{ volNum(d).toLocaleString('id-ID') }}</td>
                <td class="px-2 py-2 text-center">
                  <button type="button" class="text-rose-500 hover:text-rose-700" title="Hapus"
                    @click="removeDraft(idx)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="draft.length === 0">
                <td colspan="4" class="px-3 py-10 font-body !text-slate-400 text-center">
                  Belum ada item. Klik "+" pada referensi di kanan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-if="draft.length && !totalVolNum" class="mt-2 font-caption !text-rose-600">Isi Total Volume Order.</p>
        <p v-else-if="draft.length && Math.round(totalPersen) !== 100" class="mt-2 font-caption !text-rose-600">Total rasio harus 100%. Saat ini: {{ Math.round(totalPersen) }}%</p>
      </div>

      <div class="min-w-0">
        <h3 class="mb-2 font-strong">Referensi Harga</h3>
        <div class="gap-2 grid grid-cols-2 mb-3">
          <div>
            <FormLabel>Source Harga</FormLabel>
            <TomSelect v-model="sourceBranchIds" multiple :options="{
              placeholder: 'Pilih cabang sumber harga…',
              create: false,
              dropdownParent: 'body' as const,
              onDelete: () => true,
            }" class="mb-3 w-full">
              <option v-for="c in cabangs" :key="c.id_cabang" :value="String(c.id_cabang)">
                {{ c.nama_cabang }}
              </option>
            </TomSelect>
          </div>

          <SearchBox v-model="search" label="Cari Produk / Jenis" placeholder="Cari produk / jenis…" />
        </div>



        <div v-if="loadingRows" class="inline-flex items-center gap-2 py-4 font-body !text-slate-400">
          <Lucide icon="Loader2" class="w-4 h-4 animate-spin" /> Memuat harga…
        </div>

        <div v-else class="border border-slate-200 rounded-xl max-h-[44vh] overflow-hidden overflow-y-auto">
          <table class="divide-y divide-slate-200 w-full">
            <thead class="top-0 sticky bg-slate-50">
              <tr>
                <th class="px-3 py-2 font-label text-left">Produk</th>
                <th class="px-2 py-2 font-label text-left">Source</th>
                <th class="px-2 py-2 font-label text-right">Harga</th>
                <th class="px-2 py-2 w-10"></th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="r in referenceRows" :key="r.key" class="hover:bg-slate-50 transition">
                <td class="px-3 py-2">
                  <div class="font-strong leading-snug">
                    {{ r.nama }}<span v-if="r.jenis" class="font-body"> — {{ r.jenis }}</span>
                  </div>
                  <div v-if="r.ukuran" class="font-caption">{{ r.ukuran }}</div>
                </td>
                <td class="px-2 py-2 font-body">{{ r.source }}</td>
                <td class="px-2 py-2 font-num text-right">
                  <span v-if="r.harga > 0">{{ formatCurrency(r.harga) }}</span>
                  <span v-else class="font-label !text-amber-600">belum diisi</span>
                </td>
                <td class="px-2 py-2 text-center">
                  <button type="button" :disabled="r.added || r.harga <= 0"
                    class="inline-flex justify-center items-center border rounded-lg w-7 h-7 transition" :class="r.added
                      ? 'border-slate-200 !text-slate-300 cursor-not-allowed'
                      : 'border-primary/40 !text-primary hover:bg-primary/10'"
                    :title="r.added ? 'Sudah ditambahkan' : 'Tambah ke item'" @click="addFromRef(r)">
                    <Lucide :icon="r.added ? 'Check' : 'Plus'" class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="referenceRows.length === 0">
                <td colspan="4" class="px-3 py-10 font-body !text-slate-400 text-center">
                  Tidak ada harga untuk source / pencarian ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </FormModal>
</template>
