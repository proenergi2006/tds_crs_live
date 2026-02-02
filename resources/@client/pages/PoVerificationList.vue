<template>
  <div class="p-6 intro-y">
    <div class="flex items-center">
      <h2 class="text-lg font-medium">Daftar PO untuk Verifikasi</h2>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap">
      <div class="mx-auto text-slate-500">
        Page {{ currentPage }} of {{ totalPages }}
      </div>

      <!-- Search + Suggestions -->
      <div class="relative w-56 ml-auto">
        <FormInput
          v-model="searchQuery"
          placeholder="Search…"
          class="pr-10 !box w-full"
          @keydown.down.prevent="onArrowDown"
          @keydown.up.prevent="onArrowUp"
          @keydown.enter.prevent="onEnter"
          @keydown.esc="hideSuggestions"
          @focus="maybeOpenSuggestions"
          @blur="onBlurClose"
        >
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>

        <!-- Dropdown Suggestions -->
        <ul
          v-if="showSuggestions && (loadingSuggest || suggestions.length)"
          class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-md overflow-hidden"
          role="listbox"
        >
          <li v-if="loadingSuggest" class="px-3 py-2 text-xs text-slate-500">Memuat…</li>

          <li
            v-for="(s, i) in suggestions"
            :key="s.key"
            :class="[
              'px-3 py-2 text-sm cursor-pointer flex items-center gap-2',
              i === activeIndex ? 'bg-slate-100' : 'hover:bg-slate-50'
            ]"
            role="option"
            @mousedown.prevent="selectSuggestion(s)"
            @mouseenter="activeIndex = i"
          >
            <span class="text-[10px] uppercase text-slate-400">{{ s.type }}</span>
            <span v-html="highlight(s.label, searchQuery)"></span>
          </li>

          <li v-if="!loadingSuggest && !suggestions.length" class="px-3 py-2 text-xs text-slate-500">
            Tidak ada saran
          </li>
        </ul>
      </div>

      <FormSelect v-model="perPage" class="w-20 ml-2 !box">
        <option :value="5">5</option>
        <option :value="10">10</option>
        <option :value="25">25</option>
      </FormSelect>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nomor PO</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Vendor</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Terminal</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Status PO</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Status CFO</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Status CEO</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="(item, idx) in verificationList"
            :key="item.id_po"
            :class="rowClass(item)"
          >
            <td class="px-4 py-3 whitespace-nowrap">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap">{{ item.nomor_po }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ item.vendor?.nama_vendor ?? '-' }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ item.terminal?.nama_terminal ?? '-' }}</td>

            <!-- Status PO -->
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <span :class="poStatusClass(item.disposisi_po)">
                {{ poStatusText(item.disposisi_po) }}
              </span>
            </td>

            <!-- Status CFO -->
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <template v-if="item.cfo_result === null">—</template>
              <template v-else-if="item.cfo_result === 1">
                <Lucide icon="CheckCircle" class="text-green-500 w-5 h-5 inline-block" />
              </template>
              <template v-else>
                <Lucide icon="XCircle" class="text-red-500 w-5 h-5 inline-block" />
              </template>
            </td>

            <!-- Status CEO -->
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <template v-if="item.ceo_result === null">—</template>
              <template v-else-if="item.ceo_result === 1">
                <Lucide icon="CheckCircle" class="text-green-500 w-5 h-5 inline-block" />
              </template>
              <template v-else>
                <Lucide icon="XCircle" class="text-red-500 w-5 h-5 inline-block" />
              </template>
            </td>

            <!-- Aksi -->
            <td class="px-4 py-3 whitespace-nowrap text-center">
              <RouterLink
                :to="{ name: 'po-verification-detail', params: { id: item.id_po } }"
                class="text-blue-600 hover:underline mx-1"
              >Detail</RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-5 intro-y">
      <Pagination>
        <Pagination.Link
          :disabled="currentPage === 1"
          @click="fetchData(currentPage - 1)"
        ><Lucide icon="ChevronLeft"/></Pagination.Link>

        <Pagination.Link
          v-for="p in totalPages"
          :key="p"
          :active="p === currentPage"
          @click="fetchData(p)"
        >{{ p }}</Pagination.Link>

        <Pagination.Link
          :disabled="currentPage === totalPages"
          @click="fetchData(currentPage + 1)"
        ><Lucide icon="ChevronRight"/></Pagination.Link>
      </Pagination>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed, onUnmounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import { RouterLink } from 'vue-router'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { useAuthStore } from '@/stores/auth'

const verificationList = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)

const auth = useAuthStore()
const userRoleId = computed(() => auth.user?.id_role)

function rowClass(item: any) {
  const d = Number(item.disposisi_po)
  const isCfo = userRoleId.value === 3
  const isCeo = userRoleId.value === 2
  const highlight = (isCfo && d === 1) || (isCeo && (d === 2 || d === 3))
  return [
    'transition-colors hover:bg-slate-50',
    highlight ? 'bg-slate-100' : ''
  ]
}
function poStatusText(d: any) {
  if (d === 0) return 'Draft'
  if (d === 1) return 'Menunggu Verifikasi CFO'
  if (d === 2) return 'Menunggu Verifikasi CEO'
  if (d === 3) return 'Menunggu Verifikasi CEO' // ikut CEO
  if (d === 4) return 'Disetujui CEO'
  return '-'
}
function poStatusClass(d: any) {
  const base = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium'
  if (d === 0) return base + ' bg-slate-100 text-slate-600'
  if (d === 1) return base + ' bg-amber-100 text-amber-700'
  if (d === 2 || d === 3) return base + ' bg-indigo-100 text-indigo-700'
  if (d === 4) return base + ' bg-emerald-100 text-emerald-700'
  return base + ' bg-slate-100 text-slate-600'
}

async function fetchData(page = 1) {
  try {
    const { data } = await axios.get('/api/po-verification', {
      params: {
        page,
        per_page: perPage.value,
        search: (searchQuery.value || undefined),
      }
    })
    verificationList.value = (data.data || []).filter((it: { disposisi_po: number }) => it.disposisi_po > 0)
    currentPage.value = data.current_page
    totalPages.value = data.last_page
  } catch (e: any) {
    Swal.fire('Error', e?.response?.data?.message || 'Gagal memuat data', 'error')
  }
}

/** =======================
 *  Typeahead / Suggestions
 *  ======================= */
type SuggestItem = { key:string; type:string; label:string; value:string; id_po:number }
const suggestions = ref<SuggestItem[]>([])
const showSuggestions = ref(false)
const activeIndex = ref(-1)
const loadingSuggest = ref(false)

// Debounced: fetch list utama (300ms)
const triggerFetchData = debounce(() => fetchData(1), 300)

// Debounced: fetch suggestions (200ms)
const fetchSuggestions = debounce(async () => {
  const q = (searchQuery.value || '').trim()
  if (q.length < 2) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  try {
    loadingSuggest.value = true
    const { data } = await axios.get('/api/po-verification', {
      params: { page: 1, per_page: 8, search: q }
    })
    const rows: any[] = data?.data || []
    suggestions.value = rows.map((it) => ({
      key: `po:${it.id_po}`,
      type: 'PO',
      label: `${it.nomor_po ?? '-'} — ${it.vendor?.nama_vendor ?? '-'} — ${it.terminal?.nama_terminal ?? '-'}`,
      value: it.nomor_po ?? '',
      id_po: it.id_po
    }))
    showSuggestions.value = true
  } catch {
    suggestions.value = []
    showSuggestions.value = false
  } finally {
    loadingSuggest.value = false
  }
}, 200)

// Keyboard & UI handlers
function maybeOpenSuggestions() {
  if ((searchQuery.value || '').trim().length >= 2) showSuggestions.value = true
}
function hideSuggestions() {
  showSuggestions.value = false
  activeIndex.value = -1
}
function onBlurClose() {
  setTimeout(() => hideSuggestions(), 120) // beri waktu klik <li>
}
function onArrowDown() {
  if (!showSuggestions.value) showSuggestions.value = true
  if (!suggestions.value.length) return
  activeIndex.value = (activeIndex.value + 1) % suggestions.value.length
}
function onArrowUp() {
  if (!suggestions.value.length) return
  activeIndex.value = (activeIndex.value - 1 + suggestions.value.length) % suggestions.value.length
}
function onEnter() {
  if (showSuggestions.value && activeIndex.value >= 0) {
    selectSuggestion(suggestions.value[activeIndex.value])
  } else {
    fetchData(1)
  }
}
function selectSuggestion(s: SuggestItem) {
  searchQuery.value = s.value
  hideSuggestions()
  fetchData(1)
}

// Safe highlight (escape HTML, lalu mark kecocokan)
function escapeRegExp(s: string) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') }
function escapeHtml(s: string) {
  const v = s ?? ''
  return v
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}
function highlight(text: string, q: string) {
  const term = (q || '').trim()
  const safe = escapeHtml(text || '')
  if (!term) return safe
  const re = new RegExp(`(${escapeRegExp(term)})`, 'ig')
  return safe.replace(re, '<mark>$1</mark>')
}

// Reaksi ke input & page size
watch(searchQuery, () => {
  fetchSuggestions()
  triggerFetchData()
})
watch(perPage, () => fetchData(1))

onMounted(() => fetchData())
onUnmounted(() => {
  triggerFetchData.cancel()
  ;(fetchSuggestions as any)?.cancel?.()
})
</script>

<style scoped>
/* opsional: rapikan mark */
mark {
  background: #fef08a; /* amber-200 */
  padding: 0 2px;
  border-radius: 3px;
}
</style>
