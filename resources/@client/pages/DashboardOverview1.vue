<script setup lang="ts">
import { computed, ref, onMounted, type Component } from "vue";
import axios from "axios";
import Lucide, { type Icon } from "@/components/Base/Lucide/Lucide.vue";
import Button from "@/components/Base/Button";
import { Tab } from "@/components/Base/Headless";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import Marketing from "@/pages/Dashboard/Marketing.vue";
import Ceo from "@/pages/Dashboard/Ceo.vue";
import Om from "@/pages/Dashboard/Om.vue";

const router = useRouter();
const auth = useAuthStore();

const loading = ref(false);

const summary = ref({
  totalPenawaran: 0,
  draftPenawaran: 0,
  pendingPenawaran: 0,
  approvedPenawaran: 0,
});

const currentUser = computed(() => auth.user || {});
const isAgenRole = computed(() => currentUser.value?.brand === "proenergi");
const isAdministrator = computed(() => auth.hasRole(1));
const isMarketingRole = computed(
  () => currentUser.value?.roles?.some((r) => [4, 12].includes(r.id)) ?? false,
);

// akses cepat modul admin -- secukupnya, bukan analytics/KPI baru
const adminQuickLinks: {
  routeName: string;
  icon: Icon;
  label: string;
  description: string;
}[] = [
  {
    routeName: "users",
    icon: "Users",
    label: "Pengguna",
    description: "Kelola user, role, dan cabang.",
  },
  {
    routeName: "role-overview",
    icon: "ShieldCheck",
    label: "Role",
    description: "Atur role dan permission per role.",
  },
  {
    routeName: "permission-overview",
    icon: "Key",
    label: "Permission",
    description: "Kelola daftar permission sistem.",
  },
  {
    routeName: "approval-templates",
    icon: "ListChecks",
    label: "Approval Template",
    description: "Konfigurasi alur approval per modul.",
  },
  {
    routeName: "monitoring-app-logs",
    icon: "FileSearch",
    label: "Application Logs",
    description: "Pantau log aplikasi untuk troubleshooting.",
  },
];

// tab per role (bukan switcher) -- user dengan >1 permission dashboard lihat semua tab sekaligus, bukan cabang pertama-match saja
const availableDashboardTabs = computed<
  { key: "ceo" | "om"; label: string; component: Component }[]
>(() => {
  const tabs: { key: "ceo" | "om"; label: string; component: Component }[] = [];
  if (auth.can("dashboard.view-ceo")) {
    tabs.push({ key: "ceo", label: "CEO", component: Ceo });
  }
  if (auth.can("dashboard.view-om")) {
    tabs.push({ key: "om", label: "Operation Manager", component: Om });
  }
  return tabs;
});

const defaultTabIndex = computed<number>(() =>
  Math.max(
    0,
    availableDashboardTabs.value.findIndex(
      (t) =>
        t.key ===
        (currentUser.value?.primary_role?.id === 2
          ? "ceo"
          : currentUser.value?.primary_role?.id === 10
            ? "om"
            : null),
    ),
  ),
);

const displayName = computed(() => currentUser.value?.name || "User");

const roleLabel = computed(() => {
  return isAgenRole.value ? "Agent Tri Daya Selaras" : "Internal User";
});

const welcomeTitle = computed(() => {
  return isAgenRole.value ? "Selamat Datang, Agent TDS" : "Dashboard Utama";
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
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <!-- DASHBOARD KHUSUS ADMINISTRATOR -->
      <template v-if="isAdministrator">
        <div class="box rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
              <Lucide icon="ShieldCheck" class="h-6 w-6" />
            </div>
            <div>
              <h2 class="text-2xl font-semibold">Dashboard Administrator</h2>
              <p class="mt-1 text-slate-500">
                Halo {{ displayName }}, kelola akses dan konfigurasi sistem dari sini.
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
          <div
            v-for="item in adminQuickLinks"
            :key="item.routeName"
            class="col-span-12 sm:col-span-6 xl:col-span-4"
          >
            <button
              type="button"
              class="box flex w-full items-center gap-4 rounded-2xl p-5 text-left shadow-sm transition-colors hover:border-primary/40"
              @click="router.push({ name: item.routeName })"
            >
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Lucide :icon="item.icon" class="h-6 w-6" />
              </div>
              <div>
                <div class="font-medium text-slate-700">{{ item.label }}</div>
                <p class="mt-1 text-sm text-slate-500">{{ item.description }}</p>
              </div>
            </button>
          </div>
        </div>
      </template>

      <!-- DASHBOARD KHUSUS MARKETING / KEY ACCOUNT -->
      <template v-else-if="isMarketingRole">
        <Marketing />
      </template>

      <!-- DASHBOARD SATU TAB (CEO ATAU OM SAJA) -->
      <template v-else-if="availableDashboardTabs.length === 1">
        <component :is="availableDashboardTabs[0].component" />
      </template>

      <!-- DASHBOARD MULTI TAB (CEO + OM SEKALIGUS) -->
      <template v-else-if="availableDashboardTabs.length > 1">
        <Tab.Group :default-index="defaultTabIndex">
          <Tab.List variant="link-tabs" class="gap-1 border-b border-slate-200">
            <Tab v-for="t in availableDashboardTabs" :key="t.key" :full-width="false" v-slot="{ selected }">
              <Tab.Button class="flex items-center gap-2 px-4 py-2.5 text-sm" :class="selected
                ? 'text-primary border-b-primary font-medium'
                : 'text-slate-500 border-b-transparent hover:text-slate-700 hover:border-b-slate-300'">
                <span>{{ t.label }}</span>
              </Tab.Button>
            </Tab>
          </Tab.List>

          <Tab.Panels class="mt-4">
            <Tab.Panel v-for="t in availableDashboardTabs" :key="t.key">
              <component :is="t.component" />
            </Tab.Panel>
          </Tab.Panels>
        </Tab.Group>
      </template>

      <!-- DASHBOARD KHUSUS AGENT -->
      <template v-else-if="isAgenRole">
        <!-- HERO -->
        <div
          class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-theme-1 via-sky-600 to-cyan-700 px-8 py-8 text-white shadow-xl">
          <div class="absolute -top-12 -right-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
          <div class="absolute -bottom-10 left-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>

          <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
              <div
                class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1 text-sm font-medium backdrop-blur-sm">
                <Lucide icon="BadgeCheck" class="h-4 w-4" />
                <span>{{ roleLabel }}</span>
              </div>

              <h1 class="text-3xl font-bold leading-tight lg:text-4xl">
                {{ welcomeTitle }}
              </h1>

              <p class="mt-3 max-w-2xl text-base text-white/90 lg:text-lg">
                Halo, <span class="font-semibold">{{ displayName }}</span>. {{ welcomeSubtitle }}
              </p>

              <div class="mt-5 flex flex-wrap gap-3">
                <Button variant="secondary" class="border-0 bg-white text-slate-800 shadow hover:bg-slate-100"
                  @click="router.push({ name: 'penawarans-list-proenergi' })">
                  <Lucide icon="FileText" class="mr-2 h-4 w-4" />
                  Penawaran Agent
                </Button>

                <Button variant="outline-secondary"
                  class="border border-white/40 bg-white/10 text-white hover:bg-white/20"
                  @click="router.push({ name: 'customers-list' })">
                  <Lucide icon="Users" class="mr-2 h-4 w-4" />
                  Customer Proenergi
                </Button>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:min-w-[420px]">
              <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-white/80">Status</span>
                  <Lucide icon="Activity" class="h-5 w-5 text-white/80" />
                </div>
                <div class="mt-3 text-xl font-bold">Aktif</div>
                <div class="mt-1 text-xs text-white/75">
                  Sistem agent siap digunakan
                </div>
              </div>

              <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-white/80">Portal</span>
                  <Lucide icon="LayoutDashboard" class="h-5 w-5 text-white/80" />
                </div>
                <div class="mt-3 text-xl font-bold">Agent TDS</div>
                <div class="mt-1 text-xs text-white/75">
                  Dashboard khusus agent
                </div>
              </div>

              <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
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

        <!-- SUMMARY CARDS -->
        <div class="grid grid-cols-12 gap-6">
          <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="box rounded-2xl p-5 shadow-sm">
              <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
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
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10 text-warning">
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
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-orange-600">
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
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10 text-success">
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

        <!-- INFO PANEL -->
        <div class="box rounded-2xl p-6 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-theme-1/10 text-theme-1">
              <Lucide icon="Megaphone" class="h-6 w-6" />
            </div>
            <div>
              <h2 class="text-lg font-semibold">
                Informasi Dashboard Agent TDS
              </h2>
              <p class="text-sm text-slate-500">
                Dashboard ini khusus untuk agent Tri Daya Selaras agar
                monitoring penawaran lebih mudah.
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
              <div class="font-medium text-slate-700">
                Monitoring Approval
              </div>
              <p class="mt-2 text-sm text-slate-500">
                Pantau draft, pending, dan approved dari satu halaman.
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="font-medium text-slate-700">
                Dashboard Representatif
              </div>
              <p class="mt-2 text-sm text-slate-500">
                Tampilan telah disesuaikan untuk branding Agent Tri Daya
                Selaras.
              </p>
            </div>
          </div>
        </div>
      </template>

      <!-- DASHBOARD DEFAULT SELAIN AGENT -->
      <template v-else>
        <div class="box rounded-2xl p-8 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
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
      </template>
    </div>
  </div>
</template>
