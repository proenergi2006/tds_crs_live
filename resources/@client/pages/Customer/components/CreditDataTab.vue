<script setup lang="ts">
import { ref, computed, onMounted, useSlots } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Alert from '@/components/Base/Alert'
import { FormSelect } from "@/components/Base/Form";
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RichTextField from '@/components/SystemDesign/Form/RichTextField.vue'
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency } from '@/utils/format'

import type { FinanceAttachment } from '../types'

interface CreditRequestSnapshot {
  requested_limit: number | null;
  requested_top: number | null;
  requested_qty: string | number | null;
  product_category: string | null;
  financial_review: string | null;
}

const props = withDefaults(
  defineProps<{
    idCustomer: number
    isUnderReview: boolean
    readonly?: boolean
    latestApprovedVerification: {
      approved_limit: number | null
      approved_top: number | null
      financial_review_snapshot: string | null
      notes: string | null
      finance_attachments: FinanceAttachment[]
      reviewed_at: string | null
    } | null
    snapshot?: CreditRequestSnapshot | null
  }>(),
  { readonly: false, snapshot: null },
)

const emit = defineEmits<{ (e: 'saved'): void }>()

const { success, error: notifyError } = useNotification()
const slots = useSlots()

const CREDIT_PRODUCT_CATEGORIES = [
  { value: "crushed_stone", title: "Crushed Stone", unit: "m³" },
  { value: "polimer", title: "Polimer", unit: "totes" },
];

const loading = ref(true)
const saving = ref(false)
const requestedLimit = ref<number | null>(null)
const requestedTop = ref<number | null>(null)
const requestedQty = ref<number | null>(null);
const productCategory = ref<string>("");
const financialReview = ref<string>("");

const locked = computed<boolean>(() => props.isUnderReview || props.readonly)
const unit = computed<string>(
  () => CREDIT_PRODUCT_CATEGORIES.find((c) => c.value === productCategory.value)?.unit ?? "",
);
const financialReviewDisplay = computed<string>(() => {
  if (props.snapshot) return financialReview.value;
  return financialReview.value || props.latestApprovedVerification?.financial_review_snapshot || "";
});
const showFinanceNotesBlock = computed<boolean>(
  () => !!slots['finance-notes'] || !!props.latestApprovedVerification,
);

onMounted(() => {
  if (props.snapshot) {
    applySnapshot(props.snapshot);
    loading.value = false;
  } else {
    fetchCreditRequest();
  }
});

function applySnapshot(snapshot: CreditRequestSnapshot): void {
  requestedLimit.value = snapshot.requested_limit;
  requestedTop.value = snapshot.requested_top;
  requestedQty.value = toQtyNumber(snapshot.requested_qty);
  productCategory.value = snapshot.product_category ?? "";
  financialReview.value = snapshot.financial_review ?? "";
}

async function fetchCreditRequest(): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${props.idCustomer}/credit-request`)
    if (data) {
      requestedLimit.value = data.requested_limit
      requestedTop.value = data.requested_top
      requestedQty.value = toQtyNumber(data.requested_qty);
      productCategory.value = data.product_category ?? "";
      financialReview.value = data.financial_review ?? "";
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat pengajuan kredit.')
  } finally {
    loading.value = false
  }
}

async function saveCreditRequest(): Promise<void> {
  if (locked.value) return

  const qtyFilled = requestedQty.value !== null && requestedQty.value > 0;
  const categoryFilled = productCategory.value !== "";
  if (qtyFilled !== categoryFilled) {
    notifyError("Gagal", "Kategori produk dan volume harus diisi bersamaan.");
    return;
  }

  saving.value = true
  try {
    const { data } = await axios.put(`/api/customers/${props.idCustomer}/credit-request`, {
      requested_limit: requestedLimit.value,
      requested_top: requestedTop.value,
      requested_qty: qtyFilled ? requestedQty.value : null,
      product_category: productCategory.value === "" ? null : productCategory.value,
      financial_review: financialReview.value || null,
    })
    requestedLimit.value = data.requested_limit
    requestedTop.value = data.requested_top
    requestedQty.value = toQtyNumber(data.requested_qty);
    productCategory.value = data.product_category ?? "";
    financialReview.value = data.financial_review ?? "";
    success('Berhasil', 'Pengajuan kredit tersimpan.')
    emit('saved')
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', 'Data terkunci, verifikasi sedang berjalan.')
    } else if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      notifyError('Gagal', (Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan pengajuan kredit.')
    }
  } finally {
    saving.value = false
  }
}

function toQtyNumber(value: string | number | null): number | null {
  return value === null ? null : Number(value);
}

function formatApprovedTop(value: number | null | undefined): string {
  return value === null || value === undefined ? "-" : `${value} hari`;
}

function formatFileSize(bytes?: number | null): string {
  if (!bytes) return "";

  const units = ["B", "KB", "MB", "GB"];
  let size = bytes;
  let unitIndex = 0;

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }

  return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}
</script>

<template>
  <div class="gap-6 grid grid-cols-1">
    <Alert v-if="props.isUnderReview" variant="soft-warning">
      Verifikasi sedang berjalan. Pengajuan kredit tidak bisa diubah sampai Admin Finance memberi keputusan.
    </Alert>

    <div class="gap-6 grid grid-cols-1">
      <CardSection title="Credit Application"
        description="Pengajuan limit kredit & term of payment (TOP) untuk customer ini." icon="Wallet"
        icon-class="bg-emerald-100 text-emerald-600">
        <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[120px] text-slate-500">
          <Lucide icon="Loader2" class="w-5 h-5 animate-spin" />
          <span class="text-body">Memuat pengajuan kredit...</span>
        </div>

        <template v-else>
          <div class="border border-slate-200 rounded-xl overflow-x-auto">
            <table class="divide-y divide-slate-200 w-full min-w-[960px] table-fixed">
              <colgroup>
                <col class="w-[14%]" />
                <col class="w-[14%]" />
                <col class="w-[22%]" />
                <col class="w-[16%]" />
                <col class="w-[18%]" />
                <col class="w-[16%]" />
              </colgroup>
              <thead class="bg-slate-50">
                <tr class="divide-x divide-slate-200">
                  <th rowspan="2" class="px-3 py-2 text-form-label text-left align-middle">
                    Product
                    <RequiredAsterisk />
                  </th>
                  <th rowspan="2" class="px-3 py-2 text-form-label text-left align-middle">
                    Volume per PO
                    <RequiredAsterisk />
                  </th>
                  <th colspan="2" class="px-3 py-2 text-form-label text-center">Credit Limit</th>
                  <th colspan="2" class="px-3 py-2 text-form-label text-center">TOP</th>
                </tr>
                <tr class="border-slate-200 border-t divide-x divide-slate-200">
                  <th class="px-3 py-2 text-form-label text-left">
                    Request
                    <RequiredAsterisk />
                  </th>
                  <th class="px-3 py-2 text-form-label text-left">Approval</th>
                  <th class="px-3 py-2 text-form-label text-left">
                    Request
                    <RequiredAsterisk />
                  </th>
                  <th class="px-3 py-2 text-form-label text-left">Approval</th>
                </tr>
              </thead>
              <tbody class="bg-white">
                <tr class="divide-x divide-slate-200">
                  <td class="px-3 py-2 align-middle">
                    <FormSelect v-model="productCategory" :disabled="locked">
                      <option value="" disabled>Pilih produk</option>
                      <option v-for="category in CREDIT_PRODUCT_CATEGORIES" :key="category.value"
                        :value="category.value">
                        {{ category.title }}
                      </option>
                    </FormSelect>
                  </td>
                  <td class="px-3 py-2 align-middle">
                    <NumberField v-model="requestedQty" :suffix="unit" :decimals="2" :disabled="locked" />
                  </td>
                  <td class="px-3 py-2 align-middle">
                    <CurrencyField v-model="requestedLimit" :disabled="locked" />
                  </td>
                  <td class="px-3 py-2 align-middle">
                    <slot name="approval-limit">
                      <span class="block text-body text-right">{{
                        formatCurrency(props.latestApprovedVerification?.approved_limit) }}</span>
                    </slot>
                  </td>
                  <td class="px-3 py-2 align-middle">
                    <NumberField v-model="requestedTop" suffix="hari" :decimals="0" :disabled="locked" />
                  </td>
                  <td class="px-3 py-2 align-middle">
                    <slot name="approval-top">
                      <span class="block text-body text-right">{{
                        formatApprovedTop(props.latestApprovedVerification?.approved_top) }}</span>
                    </slot>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-3 p-4 border border-slate-200 rounded-lg">
            <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
              <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Financial Review
              <RequiredAsterisk v-if="!locked" />
            </h3>
            <RichTextField v-if="!locked" v-model="financialReview" :disabled="saving" />
            <template v-else>
              <div v-if="financialReviewDisplay" class="rich-text-content text-body" v-html="financialReviewDisplay" />
              <div v-else class="bg-slate-50 mt-1 px-3 py-2 border border-slate-200 rounded-lg text-body">-</div>
            </template>
          </div>

          <div v-if="showFinanceNotesBlock" class="mt-3 p-4 border border-slate-200 rounded-lg">
            <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
              <Lucide icon="FileText" class="w-3.5 h-3.5 shrink-0" />Catatan Admin Finance
            </h3>
            <slot name="finance-notes">
              <div v-if="props.latestApprovedVerification?.notes" class="rich-text-content text-body"
                v-html="props.latestApprovedVerification.notes" />
              <div v-else class="text-body">-</div>
              <ul v-if="props.latestApprovedVerification?.finance_attachments?.length" class="space-y-1 mt-2">
                <li v-for="attachment in props.latestApprovedVerification.finance_attachments" :key="attachment.path"
                  class="flex items-center gap-1.5">
                  <Lucide icon="Paperclip" class="w-4 h-4 text-slate-400 shrink-0" />
                  <a v-if="attachment.url" :href="attachment.url" target="_blank" class="text-body-strong text-primary">
                    {{ attachment.original_name }}
                  </a>
                  <span v-else class="text-body-strong">{{ attachment.original_name }}</span>
                  <span v-if="attachment.size_bytes != null" class="text-caption text-slate-500">
                    ({{ formatFileSize(attachment.size_bytes) }})
                  </span>
                </li>
              </ul>
            </slot>
          </div>

          <div v-if="!props.readonly" class="flex justify-end mt-5">
            <Button variant="primary" class="inline-flex items-center gap-2" :disabled="locked || saving"
              @click="saveCreditRequest">
              <Lucide v-if="saving" icon="Loader2" class="w-4 h-4 animate-spin" />
              Simpan
            </Button>
          </div>
        </template>
      </CardSection>
    </div>
  </div>
</template>
