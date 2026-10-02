<script setup lang="ts">
import { ref } from "vue";

import Alert from "@/components/Base/Alert/Alert.vue";
import Button from "@/components/Base/Button";
import { Popover } from "@/components/Base/Headless";
import Lucide from "@/components/Base/Lucide";
import Tippy from "@/components/Base/Tippy/Tippy.vue";
import ConfirmDialog from "@/components/SystemDesign/Dialog/ConfirmDialog.vue";
import DeleteRecordDialog from "@/components/SystemDesign/Dialog/DeleteRecordDialog.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import ShowcaseSection from "../components/ShowcaseSection.vue";

const { success, error, warning, info } = useNotification();

const confirmOpen = ref(false);
const deleteOpen = ref(false);
</script>

<template>
  <ShowcaseSection id="feedback" title="Feedback" description="Alert, Toast, Modal/Dialog, Tooltip, Popover.">
    <div>
      <h3 class="mb-3 text-overline">Alert</h3>
      <div class="space-y-2">
        <Alert variant="soft-success">Data berhasil disimpan.</Alert>
        <Alert variant="soft-warning">Periksa kembali data sebelum melanjutkan.</Alert>
        <Alert variant="soft-danger">Terjadi kesalahan saat memproses data.</Alert>
      </div>
      <p class="mt-2 text-caption">Dipakai nyata di Customer/Form.vue dan Verification/Customer/Detail.vue
        untuk banner peringatan inline.</p>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Toast (useNotification)</h3>
      <div class="flex flex-wrap gap-2">
        <Button variant="soft-success" @click="success('Berhasil', 'Data berhasil disimpan')">Success</Button>
        <Button variant="soft-danger" @click="error('Gagal', 'Terjadi kesalahan')">Error</Button>
        <Button variant="soft-warning" @click="warning('Perhatian', 'Periksa kembali data Anda')">Warning</Button>
        <Button variant="soft-info" @click="info('Info', 'Ini adalah notifikasi info')">Info</Button>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Modal / Dialog</h3>
      <div class="flex flex-wrap gap-2">
        <Button variant="primary" @click="confirmOpen = true">Buka ConfirmDialog</Button>
        <Button variant="soft-danger" @click="deleteOpen = true">Buka DeleteRecordDialog</Button>
      </div>

      <ConfirmDialog :open="confirmOpen" title="Konfirmasi Aksi"
        description="Apakah Anda yakin ingin melanjutkan aksi ini?" variant="primary" @close="confirmOpen = false"
        @confirm="confirmOpen = false" />

      <DeleteRecordDialog :open="deleteOpen" title="Hapus Contoh Data"
        description="Data yang dihapus tidak dapat dikembalikan." @close="deleteOpen = false"
        @confirm="deleteOpen = false" />
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
      <div>
        <h3 class="mb-3 text-overline">Tooltip (Tippy)</h3>
        <Tippy content="Ini adalah tooltip" as="span">
          <Button variant="outline-secondary">Hover saya</Button>
        </Tippy>
        <p class="mt-2 text-caption">Infrastruktur ada, belum ada pemakaian nyata di halaman produksi saat audit ini
          dibuat.</p>
      </div>

      <div>
        <h3 class="mb-3 text-overline">Popover</h3>
        <Popover class="inline-block">
          <Popover.Button as="button" type="button"
            class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 shadow-sm px-3 border border-slate-200 rounded-md h-[38px] text-body">
            <Lucide icon="SlidersHorizontal" class="w-4 h-4" />
            Filter
          </Popover.Button>
          <Popover.Panel placement="bottom-start"
            class="z-50 bg-white shadow-lg mt-2 p-3 border border-slate-200 rounded-xl w-56">
            <p class="text-body">Konten popover di sini.</p>
          </Popover.Panel>
        </Popover>
        <p class="mt-2 text-caption">Dipakai nyata di DataListToolbar untuk panel filter.</p>
      </div>
    </div>
  </ShowcaseSection>
</template>
