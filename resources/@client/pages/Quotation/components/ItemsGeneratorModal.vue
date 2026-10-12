<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'

import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import { FormLabel } from '@/components/Base/Form'
import SearchBox from '@/components/SystemDesign/Form/SearchBox.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import type { QuotationItemLine, TransportBasis } from '@/types'

const props = withDefaults(
  defineProps<{
    open: boolean
    pricePeriodId: string | number | null
    periodLabel?: string
    cabangs: any[]
    produks: any[]
    initialItems?: QuotationItemLine[]
    usePe?: boolean
  }>(),
  { periodLabel: '', initialItems: () => [], usePe: false },
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'save', items: QuotationItemLine[]): void
}>()

const sourceBranchIds = ref<string[]>([])
const rows = ref<any[]>([])
const loadingRows = ref(false)
const search = ref('')
const draft = ref<QuotationItemLine[]>([])

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
      product_id: String(x.product_id ?? ''),
      source_branch_id: String(x.source_branch_id ?? ''),
      product_price_id: x.product_price_id ?? null,
      harga_price_list: Number(x.harga_price_list ?? 0),
      source_name: x.source_name || (x.source_branch_id ? branchName(x.source_branch_id) : ''),
      quantity: Number(x.quantity ?? 0),
      unit_price: Number(x.unit_price ?? x.harga_price_list ?? 0),
      cogs_basis: x.cogs_basis ?? null,
      transport_price: x.transport_price ?? null,
      saved_transport_price: x.saved_transport_price ?? null,
    }))

    const fromDraft = [...new Set(draft.value.map(d => d.source_branch_id).filter(Boolean))]
    if (fromDraft.length) {
      sourceBranchIds.value = fromDraft
    } else {
      const jkt = props.cabangs.find(c => String(c.nama_cabang ?? '').toLowerCase().includes('jakarta'))
      const first = jkt || props.cabangs[0]
      sourceBranchIds.value = first ? [String(first.id_cabang)] : []
    }

    sortDraft()
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
    d => d.product_id === String(productId) && d.source_branch_id === String(branchId),
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
        cogs_basis: (r.cogs_basis ?? null) as TransportBasis | null,
        transport_price: r.transport_price === null || r.transport_price === undefined ? null : Number(r.transport_price),
        added: isAdded(r.product_id, r.branch_id),
      }
    })
    .filter(r => !q || r.nama.toLowerCase().includes(q) || r.jenis.toLowerCase().includes(q))
    .sort((a, b) => a.nama.localeCompare(b.nama) || a.source.localeCompare(b.source))
})

function addFromRef(ref: any) {
  if (ref.added) return
  draft.value.push({
    product_id: String(ref.product_id),
    source_branch_id: String(ref.branch_id),
    product_price_id: ref.product_price_id ?? null,
    harga_price_list: ref.harga,
    source_name: ref.source,
    quantity: 0,
    unit_price: ref.harga,
    cogs_basis: ref.cogs_basis ?? null,
    transport_price: ref.transport_price ?? null,
    saved_transport_price: null,
  })
  sortDraft()
}

function removeDraft(idx: number) {
  draft.value.splice(idx, 1)
  sortDraft()
}

function draftProdukLabel(id: string): string {
  const p = produkById.value[String(id)]
  if (!p) return `#${id}`
  const uk = p.ukuran?.nama_ukuran ? ` · ${p.ukuran.nama_ukuran}${p.ukuran?.satuan?.nama_satuan ? ' ' + p.ukuran.satuan.nama_satuan : ''}` : ''
  return `${p.nama_produk}${uk}`
}

// sengaja gak dipanggil saat user ngetik quantity/harga — nyortir sambil ngetik mindahin row, fokus input kelempar
function sortDraft() {
  draft.value.sort((a, b) => {
    const pa = produkById.value[String(a.product_id)] || {}
    const pb = produkById.value[String(b.product_id)] || {}
    return (
      String(pa.nama_produk ?? '').localeCompare(String(pb.nama_produk ?? ''), 'id') ||
      String(pa.ukuran?.nama_ukuran ?? '').localeCompare(String(pb.ukuran?.nama_ukuran ?? ''), 'id') ||
      String(a.source_name ?? '').localeCompare(String(b.source_name ?? ''), 'id')
    )
  })
}

function lineTotal(d: QuotationItemLine): number {
  return Math.round(Number(d.quantity) * Number(d.unit_price) * 100) / 100
}

const totalQuantity = computed<number>(() =>
  draft.value.reduce((s, d) => s + Number(d.quantity || 0), 0),
)
const subtotalProducts = computed<number>(() =>
  Math.round(draft.value.reduce((s, d) => s + lineTotal(d), 0) * 100) / 100,
)
const linesValid = computed<boolean>(() =>
  draft.value.every(d =>
    d.product_id && d.source_branch_id && Number(d.quantity) > 0 && Number(d.unit_price) > 0,
  ),
)
const canSave = computed(() => draft.value.length > 0 && linesValid.value)

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
          <h3 class="text-body-strong">Item Penawaran ({{ draft.length }})</h3>
          <span class="text-caption !text-slate-500">
            {{ totalQuantity.toLocaleString('id-ID') }} vol · {{ formatCurrency(subtotalProducts) }}
          </span>
        </div>

        <div class="border border-slate-200 rounded-xl overflow-x-auto">
          <table class="divide-y divide-slate-200 w-full">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-3 py-2 text-form-label text-left">Produk / Source</th>
                <th class="px-2 py-2 w-24 text-form-label text-right">Quantity</th>
                <th class="px-2 py-2 w-36 text-form-label text-right">Harga per unit</th>
                <th class="px-2 py-2 w-32 text-form-label text-right">Subtotal</th>
                <th class="px-2 py-2 w-10"></th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="(d, idx) in draft" :key="d.product_id + '-' + d.source_branch_id">
                <td class="px-3 py-2">
                  <div class="text-body-strong leading-snug">{{ draftProdukLabel(d.product_id) }}</div>
                  <div class="text-caption">
                    {{ d.source_name }} · {{ formatCurrency(d.harga_price_list) }}
                  </div>
                </td>
                <td class="px-2 py-2">
                  <NumberField v-model="d.quantity" :min="0" :decimals="2" />
                </td>
                <td class="px-2 py-2">
                  <CurrencyField v-model="d.unit_price" />
                </td>
                <td class="px-2 py-2 num-sm text-right">{{ formatCurrency(lineTotal(d)) }}</td>
                <td class="px-2 py-2 text-center">
                  <button type="button" class="text-rose-500 hover:text-rose-700" title="Hapus"
                    @click="removeDraft(idx)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="draft.length === 0">
                <td colspan="5" class="px-3 py-10 text-body !text-slate-400 text-center">
                  Belum ada item. Klik "+" pada referensi di kanan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-if="draft.length && !linesValid" class="mt-2 text-caption !text-rose-600">
          Quantity dan harga per unit setiap item wajib lebih dari 0.
        </p>
      </div>

      <div class="min-w-0">
        <h3 class="mb-2 text-body-strong">Referensi Harga</h3>
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



        <div v-if="loadingRows" class="inline-flex items-center gap-2 py-4 text-body !text-slate-400">
          <Lucide icon="Loader2" class="w-4 h-4 animate-spin" /> Memuat harga…
        </div>

        <div v-else class="border border-slate-200 rounded-xl max-h-[44vh] overflow-hidden overflow-y-auto">
          <table class="divide-y divide-slate-200 w-full">
            <thead class="top-0 sticky bg-slate-50">
              <tr>
                <th class="px-3 py-2 text-form-label text-left">Produk</th>
                <th class="px-2 py-2 text-form-label text-left">Source</th>
                <th class="px-2 py-2 text-form-label text-right">Harga</th>
                <th class="px-2 py-2 w-10"></th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="r in referenceRows" :key="r.key" class="hover:bg-slate-50 transition">
                <td class="px-3 py-2">
                  <div class="text-body-strong leading-snug">
                    {{ r.nama }}<span v-if="r.jenis" class="text-body"> — {{ r.jenis }}</span>
                  </div>
                  <div v-if="r.ukuran" class="text-caption">{{ r.ukuran }}</div>
                </td>
                <td class="px-2 py-2 text-body">{{ r.source }}</td>
                <td class="px-2 py-2 num-sm text-right">
                  <span v-if="r.harga > 0">{{ formatCurrency(r.harga) }}</span>
                  <span v-else class="text-form-label !text-amber-600">belum diisi</span>
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
                <td colspan="4" class="px-3 py-10 text-body !text-slate-400 text-center">
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
