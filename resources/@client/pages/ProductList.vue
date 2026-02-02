<script setup lang="ts">
import _ from "lodash";
import { ref, onMounted } from "vue";
import axios from "axios";
import Button from "@/components/Base/Button";
import Pagination from "@/components/Base/Pagination";
import { FormInput, FormSelect } from "@/components/Base/Form";
import Lucide from "@/components/Base/Lucide";
import Tippy from "@/components/Base/Tippy";
import { Dialog, Menu } from "@/components/Base/Headless";
import Table from "@/components/Base/Table";

const deleteConfirmationModal = ref(false);
const deleteButtonRef = ref(null);
const setDeleteConfirmationModal = (value: boolean) => {
  deleteConfirmationModal.value = value;
};

const products = ref<any[]>([]);
const search = ref('');
const perPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);

async function fetchProducts(page = 1) {
  try {
    const res = await axios.get('/api/produks', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value
      }
    });
    products.value = res.data.data;
    currentPage.value = res.data.current_page;
    totalPages.value = res.data.last_page;
  } catch (err) {
    console.error(err);
  }
}

onMounted(() => {
  fetchProducts();
});
</script>

<template>
  <h2 class="mt-10 text-lg font-medium intro-y">Product List</h2>
  <div class="grid grid-cols-12 gap-6 mt-5">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center col-span-12 mt-2 intro-y sm:flex-nowrap">
      <Button variant="primary" class="mr-2 shadow-md">Add New Product</Button>
      <div class="w-full mt-3 sm:w-auto sm:mt-0 sm:ml-auto md:ml-0">
        <div class="relative w-56 text-slate-500">
          <FormInput v-model="search" type="text" class="w-56 pr-10 !box" placeholder="Search..." @input="fetchProducts(1)" />
          <Lucide icon="Search" class="absolute inset-y-0 right-0 w-4 h-4 my-auto mr-3" />
        </div>
      </div>
    </div>

    <!-- Product Table -->
    <div class="col-span-12 overflow-auto intro-y lg:overflow-visible">
      <Table class="border-spacing-y-[10px] border-separate -mt-2">
        <Table.Thead>
          <Table.Tr>
            <Table.Th>Product Name</Table.Th>
            <Table.Th>Jenis</Table.Th>
            <Table.Th>Stock</Table.Th>
            <Table.Th>Price</Table.Th>
            <Table.Th>Status</Table.Th>
            <Table.Th class="text-center">Actions</Table.Th>
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>
          <Table.Tr v-for="product in products" :key="product.id">
            <Table.Td>{{ product.nama_produk }}</Table.Td>
            <Table.Td>{{ product.jenis?.nama || '-' }}</Table.Td>
            <Table.Td>{{ product.stok || '-' }}</Table.Td>
            <Table.Td>${{ product.harga || '0.00' }}</Table.Td>
            <Table.Td>
              <span :class="product.is_active ? 'text-success' : 'text-danger'">
                <Lucide icon="CheckSquare" class="w-4 h-4 inline-block mr-1" />
                {{ product.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <a class="text-blue-600 hover:text-blue-800 mx-2" href="#">Edit</a>
              <a class="text-red-600 hover:text-red-800 mx-2" href="#" @click.prevent="setDeleteConfirmationModal(true)">Delete</a>
            </Table.Td>
          </Table.Tr>
        </Table.Tbody>
      </Table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center col-span-12 mt-5">
      <Pagination>
        <Pagination.Link @click="fetchProducts(currentPage - 1)" :disabled="currentPage === 1">
          <Lucide icon="ChevronLeft" class="w-4 h-4" />
        </Pagination.Link>
        <Pagination.Link v-for="page in totalPages" :key="page" @click="fetchProducts(page)" :active="page === currentPage">
          {{ page }}
        </Pagination.Link>
        <Pagination.Link @click="fetchProducts(currentPage + 1)" :disabled="currentPage === totalPages">
          <Lucide icon="ChevronRight" class="w-4 h-4" />
        </Pagination.Link>
      </Pagination>
      <FormSelect class="w-20 ml-2 !box" v-model="perPage" @change="fetchProducts(1)">
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
      </FormSelect>
    </div>

    <!-- Delete Confirmation Modal -->
    <Dialog :open="deleteConfirmationModal" @close="() => setDeleteConfirmationModal(false)" :initialFocus="deleteButtonRef">
      <Dialog.Panel>
        <div class="p-5 text-center">
          <Lucide icon="XCircle" class="w-16 h-16 mx-auto text-danger" />
          <div class="mt-5 text-3xl">Are you sure?</div>
          <div class="mt-2 text-slate-500">This action cannot be undone.</div>
        </div>
        <div class="px-5 pb-8 text-center">
          <Button variant="outline-secondary" class="w-24 mr-1" @click="setDeleteConfirmationModal(false)">Cancel</Button>
          <Button variant="danger" class="w-24" ref="deleteButtonRef">Delete</Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  </div>
</template>
