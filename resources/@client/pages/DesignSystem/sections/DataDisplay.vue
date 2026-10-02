<script setup lang="ts">
import Table from "@/components/Base/Table";
import Badge from "@/components/SystemDesign/Data/Badge.vue";
import DataList from "@/components/SystemDesign/Data/DataList.vue";
import Stepper from "@/components/SystemDesign/Stepper/Stepper.vue";
import ShowcaseSection from "../components/ShowcaseSection.vue";

const sampleRows = [
  { id: 1, name: "PT Sinergi Logistik", status: "Active" },
  { id: 2, name: "CV Jaya Crane", status: "Inactive" },
];

const steps = [
  { title: "Diajukan", status: "completed" as const },
  { title: "Diperiksa BM", status: "completed" as const },
  { title: "Menunggu OM", status: "active" as const },
  { title: "Selesai", status: "pending" as const },
];
</script>

<template>
  <ShowcaseSection id="data-display" title="Data Display"
    description="Card (.box), Table, DataList (loading & empty state bawaan), Stepper.">
    <div>
      <h3 class="mb-3 text-overline">Card (.box)</h3>
      <div class="p-4 max-w-sm box">
        <p class="text-body-strong">Judul Card</p>
        <p class="mt-1 text-body">Wrapper `.box` dipakai sebagai permukaan standar di seluruh aplikasi.</p>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Table (raw)</h3>
      <div class="p-0 overflow-hidden box">
        <Table>
          <Table.Thead>
            <Table.Tr>
              <Table.Th>Nama</Table.Th>
              <Table.Th>Status</Table.Th>
            </Table.Tr>
          </Table.Thead>
          <Table.Tbody>
            <Table.Tr v-for="r in sampleRows" :key="r.id">
              <Table.Td>{{ r.name }}</Table.Td>
              <Table.Td>
                <Badge :variant="r.status === 'Active' ? 'soft-success' : 'soft-secondary'">{{ r.status }}</Badge>
              </Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">DataList — terisi</h3>
      <DataList :colspan="2" :loading="false" :empty="false">
        <template #head>
          <Table.Th>Nama</Table.Th>
          <Table.Th>Status</Table.Th>
        </template>
        <template #body>
          <Table.Tr v-for="r in sampleRows" :key="r.id">
            <Table.Td>{{ r.name }}</Table.Td>
            <Table.Td>
              <Badge :variant="r.status === 'Active' ? 'soft-success' : 'soft-secondary'">{{ r.status }}</Badge>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
      <div>
        <h3 class="mb-3 text-overline">DataList — loading</h3>
        <DataList :colspan="2" :loading="true" :empty="false">
          <template #head>
            <Table.Th>Nama</Table.Th>
            <Table.Th>Status</Table.Th>
          </template>
          <template #body />
        </DataList>
      </div>

      <div>
        <h3 class="mb-3 text-overline">DataList — empty</h3>
        <DataList :colspan="2" :loading="false" :empty="true" empty-description="Belum ada data untuk ditampilkan.">
          <template #head>
            <Table.Th>Nama</Table.Th>
            <Table.Th>Status</Table.Th>
          </template>
          <template #body />
        </DataList>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Stepper</h3>
      <Stepper :steps="steps" direction="horizontal" size="md" />
    </div>
  </ShowcaseSection>
</template>
