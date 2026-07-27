<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'

defineProps<{
  idCustomer: number
}>()
</script>

<template>
  <div class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
    <Lucide icon="Wrench" class="h-8 w-8 text-slate-400" />
    <div class="font-strong">Credit Application / TOP Segera Hadir</div>
    <div class="font-body max-w-md">
      Halaman pengajuan kredit & TOP sedang dalam tahap perbaikan dan akan tersedia kembali pada pekerjaan
      berikutnya.
    </div>
  </div>
</template>
