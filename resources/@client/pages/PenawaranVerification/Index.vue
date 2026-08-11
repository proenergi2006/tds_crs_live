<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

import Lucide from '@/components/Base/Lucide'
import { formatDate } from '@/utils/format'

/* State */
const route = useRoute()
const token = route.params.token as string

type PageState = 'loading' | 'verified' | 'invalid'

interface VerificationResponse {
  verified: boolean
  nomor_penawaran?: string
  status_label?: string
  tanggal_approval?: string | null
}

const pageState = ref<PageState>('loading')
const verification = ref<VerificationResponse | null>(null)

/* Fetch */
// verified:false dan 404 sengaja dipetakan ke state 'invalid' yang sama, biar gak bocorin status dokumen ke publik
async function fetchVerification(): Promise<void> {
  try {
    const { data } = await axios.get<VerificationResponse>(`/api/verifikasi-penawaran/${token}`)
    if (data.verified) {
      verification.value = data
      pageState.value = 'verified'
    } else {
      pageState.value = 'invalid'
    }
  } catch (e) {
    pageState.value = 'invalid'
  }
}
onMounted(fetchVerification)

// halaman ini di luar Layout.vue jadi ThemeSwitcher gak pernah mount -- tanpa class ini warna jatuh ke default
onMounted(() => { document.documentElement.classList.add('theme-1') })
onUnmounted(() => { document.documentElement.classList.remove('theme-1') })
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div v-if="pageState === 'loading'" class="flex min-h-screen items-center justify-center gap-2 text-slate-500">
      <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
      <span class="font-body">Memuat halaman...</span>
    </div>

    <div v-else-if="pageState === 'verified'" class="flex min-h-screen items-center justify-center px-5 py-16">
      <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
          <Lucide icon="ShieldCheck" class="h-8 w-8 text-emerald-600" />
        </div>
        <h1 class="font-header mt-5 text-xl">Dokumen Terverifikasi</h1>
        <p class="font-caption mt-2">
          Halaman ini menampilkan status verifikasi resmi dari dokumen penawaran berikut.
        </p>

        <div class="mt-6 space-y-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left">
          <div>
            <div class="font-label">Nomor Penawaran</div>
            <div class="font-strong mt-1">{{ verification?.nomor_penawaran }}</div>
          </div>
          <div>
            <div class="font-label">Status</div>
            <div class="mt-1">
              <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 font-label text-emerald-700">
                {{ verification?.status_label }}
              </span>
            </div>
          </div>
          <div v-if="verification?.tanggal_approval">
            <div class="font-label">Tanggal Approval</div>
            <div class="font-strong mt-1">{{ formatDate(verification.tanggal_approval) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="flex min-h-screen items-center justify-center px-5 py-16">
      <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
          <Lucide icon="SearchX" class="h-8 w-8 text-slate-500" />
        </div>
        <h1 class="font-header mt-5 text-xl">Dokumen Tidak Ditemukan</h1>
        <p class="font-caption mt-2">Dokumen tidak ditemukan atau belum terverifikasi.</p>
      </div>
    </div>
  </div>
</template>
