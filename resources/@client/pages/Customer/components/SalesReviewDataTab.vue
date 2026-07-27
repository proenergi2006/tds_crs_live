<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'

defineProps<{
  idCustomer: number
}>()
</script>

<template>
  <div class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
    <Lucide icon="Wrench" class="h-8 w-8 text-slate-400" />
    <div class="font-strong">Sales Review Segera Hadir</div>
    <div class="font-body max-w-md">
      Halaman Sales Review sedang disusun ulang (rencana: persisten per customer, tidak reset saat siklus verifikasi
      baru dibuat) dan akan tersedia kembali pada pekerjaan berikutnya.
    </div>
  </div>
</template>
