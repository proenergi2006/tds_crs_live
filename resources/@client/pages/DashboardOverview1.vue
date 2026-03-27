<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import axios from "axios";
import Lucide from "@/components/Base/Lucide";
import Button from "@/components/Base/Button";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const auth = useAuthStore();

const loading = ref(false);

const summary = ref({
  totalPenawaran: 0,
  draftPenawaran: 0,
  pendingPenawaran: 0,
  approvedPenawaran: 0,
});

const agenRoles = [13, 14, 15, 16];

const currentUser = computed(() => auth.user || {});
const isAgenRole = computed(() =>
  agenRoles.includes(Number(currentUser.value?.id_role))
);

const displayName = computed(() => currentUser.value?.name || "User");

const roleLabel = computed(() => {
  return isAgenRole.value ? "Agent Tri Daya Selaras" : "Internal User";
});

const welcomeTitle = computed(() => {
  return isAgenRole.value
    ? "Selamat Datang, Agent TDS"
    : "Dashboard Utama";
});

const welcomeSubtitle = computed(() => {
  return isAgenRole.value
    ? "Kelola penawaran, pantau status approval, dan jalankan aktivitas operasional agent dengan lebih cepat."
    : "Pantau aktivitas sistem dan data operasional dari dashboard utama.";
});

async function fetchSummary() {
  if (!isAgenRole.value) return;

  loading.value = true;
  try {
    const { data } = await axios.get("/api/dashboard/agent-summary");
    summary.value = {
      totalPenawaran: Number(data.total_penawaran || 0),
      draftPenawaran: Number(data.draft_penawaran || 0),
      pendingPenawaran: Number(data.pending_penawaran || 0),
      approvedPenawaran: Number(data.approved_penawaran || 0),
    };
  } catch (error) {
    console.error("Gagal memuat dashboard agent", error);
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  if (!auth.user) {
    try {
      await auth.fetchUser();
    } catch (e) {
      console.error(e);
    }
  }

  await fetchSummary();
});
</script>

<template>
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12">
      <!-- DASHBOARD KHUSUS AGENT -->
      <template v-if="isAgenRole">
        <!-- HERO -->
        <div class="col-span-12 mt-8 intro-y">
          <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-theme-1 via-sky-600 to-cyan-700 px-8 py-8 text-white shadow-xl"
          >
            <div
              class="absolute -top-12 -right-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"
            ></div>
            <div
              class="absolute -bottom-10 left-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"
            ></div>

            <div
              class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
            >
              <div class="max-w-3xl">
                <div
                  class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1 text-sm font-medium backdrop-blur-sm"
                >
                  <Lucide icon="BadgeCheck" class="h-4 w-4" />
                  <span>{{ roleLabel }}</span>
                </div>

                <h1 class="text-3xl font-bold leading-tight lg:text-4xl">
                  {{ welcomeTitle }}
                </h1>

                <p class="mt-3 max-w-2xl text-base text-white/90 lg:text-lg">
                  Halo, <span class="font-semibold">{{ displayName }}</span
                  >. {{ welcomeSubtitle }}
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                  <Button
                    variant="secondary"
                    class="border-0 bg-white text-slate-800 shadow hover:bg-slate-100"
                    @click="router.push({ name: 'penawarans-list-proenergi' })"
                  >
                    <Lucide icon="FileText" class="mr-2 h-4 w-4" />
                    Penawaran Agent
                  </Button>

                  <Button
                    variant="outline-secondary"
                    class="border border-white/40 bg-white/10 text-white hover:bg-white/20"
                    @click="router.push({ name: 'customers-list-proenergi' })"
                  >
                    <Lucide icon="Users" class="mr-2 h-4 w-4" />
                    Customer Proenergi
                  </Button>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:min-w-[420px]">
                <div
                  class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-white/80">Status</span>
                    <Lucide icon="Activity" class="h-5 w-5 text-white/80" />
                  </div>
                  <div class="mt-3 text-xl font-bold">Aktif</div>
                  <div class="mt-1 text-xs text-white/75">
                    Sistem agent siap digunakan
                  </div>
                </div>

                <div
                  class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-white/80">Portal</span>
                    <Lucide icon="LayoutDashboard" class="h-5 w-5 text-white/80" />
                  </div>
                  <div class="mt-3 text-xl font-bold">Agent TDS</div>
                  <div class="mt-1 text-xs text-white/75">
                    Dashboard khusus agent
                  </div>
                </div>

                <div
                  class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-white/80">Brand</span>
                    <Lucide icon="Sparkles" class="h-5 w-5 text-white/80" />
                  </div>
                  <div class="mt-3 text-xl font-bold">Tri Daya Selaras</div>
                  <div class="mt-1 text-xs text-white/75">
                    Operasional agent
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="col-span-12 intro-y mt-6">
          <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
              <div class="box rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary"
                  >
                    <Lucide icon="FileSpreadsheet" class="h-6 w-6" />
                  </div>
                  <span class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary">
                    Total
                  </span>
                </div>
                <div class="mt-4 text-sm text-slate-500">Total Penawaran</div>
                <div class="mt-1 text-2xl font-bold">
                  {{ summary.totalPenawaran.toLocaleString("id-ID") }}
                </div>
                <div class="mt-1 text-xs text-slate-400">
                  Semua data penawaran agent
                </div>
              </div>
            </div>

            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
              <div class="box rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10 text-warning"
                  >
                    <Lucide icon="FileEdit" class="h-6 w-6" />
                  </div>
                  <span class="rounded-full bg-warning/10 px-2 py-1 text-xs text-warning">
                    Draft
                  </span>
                </div>
                <div class="mt-4 text-sm text-slate-500">Draft</div>
                <div class="mt-1 text-2xl font-bold">
                  {{ summary.draftPenawaran.toLocaleString("id-ID") }}
                </div>
                <div class="mt-1 text-xs text-slate-400">
                  Penawaran belum diajukan
                </div>
              </div>
            </div>

            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
              <div class="box rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600"
                  >
                    <Lucide icon="Clock3" class="h-6 w-6" />
                  </div>
                  <span class="rounded-full bg-orange-100 px-2 py-1 text-xs text-orange-600">
                    Pending
                  </span>
                </div>
                <div class="mt-4 text-sm text-slate-500">Menunggu Approval</div>
                <div class="mt-1 text-2xl font-bold">
                  {{ summary.pendingPenawaran.toLocaleString("id-ID") }}
                </div>
                <div class="mt-1 text-xs text-slate-400">
                  Perlu tindak lanjut approval
                </div>
              </div>
            </div>

            <div class="col-span-12 sm:col-span-6 xl:col-span-3">
              <div class="box rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10 text-success"
                  >
                    <Lucide icon="BadgeCheck" class="h-6 w-6" />
                  </div>
                  <span class="rounded-full bg-success/10 px-2 py-1 text-xs text-success">
                    Approved
                  </span>
                </div>
                <div class="mt-4 text-sm text-slate-500">Disetujui</div>
                <div class="mt-1 text-2xl font-bold">
                  {{ summary.approvedPenawaran.toLocaleString("id-ID") }}
                </div>
                <div class="mt-1 text-xs text-slate-400">
                  Penawaran yang sudah lolos
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- INFO PANEL -->
        <div class="col-span-12 intro-y mt-6">
          <div class="box rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-3">
              <div
                class="flex h-12 w-12 items-center justify-center rounded-full bg-theme-1/10 text-theme-1"
              >
                <Lucide icon="Megaphone" class="h-6 w-6" />
              </div>
              <div>
                <h2 class="text-lg font-semibold">Informasi Dashboard Agent TDS</h2>
                <p class="text-sm text-slate-500">
                  Dashboard ini khusus untuk agent Tri Daya Selaras agar monitoring penawaran lebih mudah.
                </p>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-medium text-slate-700">Akses Cepat</div>
                <p class="mt-2 text-sm text-slate-500">
                  Langsung menuju customer, penawaran, dan proses kerja agent.
                </p>
              </div>

              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-medium text-slate-700">Monitoring Approval</div>
                <p class="mt-2 text-sm text-slate-500">
                  Pantau draft, pending, dan approved dari satu halaman.
                </p>
              </div>

              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-medium text-slate-700">Dashboard Representatif</div>
                <p class="mt-2 text-sm text-slate-500">
                  Tampilan telah disesuaikan untuk branding Agent Tri Daya Selaras.
                </p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- DASHBOARD DEFAULT SELAIN AGENT -->
      <template v-else>
        <div class="col-span-12 mt-8 intro-y">
          <div class="box rounded-2xl p-8 shadow-sm">
            <div class="flex items-center gap-3">
              <div
                class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary"
              >
                <Lucide icon="LayoutDashboard" class="h-7 w-7" />
              </div>
              <div>
                <h2 class="text-2xl font-semibold">{{ welcomeTitle }}</h2>
                <p class="mt-1 text-slate-500">
                  Halo {{ displayName }}, {{ welcomeSubtitle }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>