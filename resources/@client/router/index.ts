import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

import { startRouteLoading, stopRouteLoading } from "@/utils/routeLoading";

declare module "vue-router" {
  interface RouteMeta {
    title?: string;
    breadcrumbTitle?: string;
    brand?: string;
    role?: string;
    permission?: string;
    guestOnly?: boolean;
  }
}

const routes = [
  {
    path: "/login",
    name: "login",
    meta: { title: "Login", guestOnly: true },
    component: () => import("@/pages/Login.vue"),
  },

  {
    path: "/two-factor",
    name: "two-factor",
    meta: { guestOnly: true },
    component: () => import("@/pages/TwoFactor.vue"),
  },

  {
    path: "/forgot-password",
    name: "forgot-password",
    component: () => import("@/pages/ForgotPassword.vue"),
  },

  {
    path: "/customer-onboarding/:token",
    name: "customer-onboarding",
    component: () => import("@/pages/CustomerOnboarding/Index.vue"),
  },

  {
    path: "/",
    component: () => import("@/themes/Layout.vue"),
    children: [
      {
        path: "",
        name: "dashboard-overview-1",
        meta: { title: "Dashboard Overview" },
        component: () => import("@/pages/DashboardOverview1.vue"),
      },
      {
        path: "role-overview",
        name: "role-overview",
        component: () => import("@/pages/Role/Index.vue"),
        meta: { permission: "admin.users.manage" },
      },
      {
        path: "/permission-overview",
        name: "permission-overview",
        component: () => import("@/pages/Permission/Index.vue"),
        meta: { permission: "admin.users.manage" },
      },
      {
        path: "users",
        name: "users",
        component: () => import("@/pages/Users.vue"),
        meta: { permission: "admin.users.manage" },
      },
      {
        path: "profile-overview-1",
        name: "profile-overview-1",
        component: () => import("@/pages/ProfileOverview1.vue"),
      },
      {
        path: "profile-overview-2",
        name: "profile-overview-2",
        component: () => import("@/pages/ProfileOverview2.vue"),
        meta: { permission: "admin.users.manage" },
      },
      {
        path: "profile-overview-3",
        name: "profile-overview-3",
        component: () => import("@/pages/ProfileOverview3.vue"),
        meta: { permission: "admin.users.manage" },
      },
      {
        path: "crud-data-list",
        name: "crud-data-list",
        component: () => import("../pages/CrudDataList.vue"),
      },
      {
        path: "crud-form",
        name: "crud-form",
        component: () => import("../pages/CrudForm.vue"),
      },
      {
        path: "cabang",
        name: "cabang",
        component: () => import("@/pages/Cabang.vue"),
        meta: { permission: "master-data.cabang.manage" },
      },
      {
        path: "satuan",
        name: "satuan",
        component: () => import("@/pages/MasterData/Satuan/Index.vue"),
        meta: { permission: "master-data.view" },
      },
      {
        path: "ukuran",
        name: "ukuran",
        component: () => import("@/pages/MasterData/Ukuran/Index.vue"),
        meta: { permission: "master-data.view" },
      },
      {
        path: "produks-list",
        name: "produks-list",
        component: () => import("@/pages/MasterData/Produk/Index.vue"),
        meta: { permission: "master-data.view" },
      },
      {
        path: "produk-hargas",
        name: "produk-hargas",
        component: () => import("@/pages/MasterData/HargaProduk/Index.vue"),
        meta: { permission: "harga-produk.view" },
      },
      {
        path: "produk-hargas/:id/detail",
        name: "produk-hargas-detail",
        component: () => import("@/pages/MasterData/HargaProduk/Detail.vue"),
        meta: {
          breadcrumbTitle: "Detail Harga Produk",
          permission: "harga-produk.view",
        },
      },
      {
        path: "produk-hargas/create",
        name: "produk-hargas-create",
        component: () => import("@/pages/MasterData/HargaProduk/Form.vue"),
        meta: {
          permission: "harga-produk.manage",
          breadcrumbTitle: "Tambah Harga Produk",
        },
      },
      {
        path: "produk-hargas/:id/edit",
        name: "produk-hargas-edit",
        component: () => import("@/pages/MasterData/HargaProduk/Form.vue"),
        meta: {
          permission: "harga-produk.view",
          breadcrumbTitle: "Edit Harga Produk",
        },
      },
      {
        path: "attachment-harga-dasar",
        name: "attachment-harga-dasar-list",
        component: () => import("@/pages/AttachmentHargaDasarList.vue"),
        meta: { permission: "harga-produk.view" },
      },
      {
        path: "attachment-harga-dasar/create",
        name: "attachment-harga-dasar-create",
        component: () => import("@/pages/AttachmentHargaDasarCreate.vue"),
        meta: { permission: "harga-produk.view" },
      },
      {
        path: "attachment-harga-dasar/:id/edit",
        name: "attachment-harga-dasar-edit",
        component: () => import("@/pages/AttachmentHargaDasarEdit.vue"),
        meta: { permission: "harga-produk.view" },
      },
      {
        path: "provinsis",
        name: "provinsi-list",
        component: () => import("@/pages/ProvinsiList.vue"),
        meta: { permission: "master-data.wilayah.manage" },
      },
      {
        path: "provinsis/create",
        name: "provinsi-create",
        component: () => import("@/pages/ProvinsiCreate.vue"),
        meta: { permission: "master-data.wilayah.manage" },
      },
      {
        path: "provinsis/:id/edit",
        name: "provinsi-edit",
        component: () => import("@/pages/ProvinsiEdit.vue"),
        meta: { permission: "master-data.wilayah.manage" },
      },
      {
        path: "calendar",
        name: "calendar",
        component: () => import("@/pages/Calendar.vue"),
      },
      {
        path: "validation",
        name: "validation",
        component: () => import("@/pages/Validation.vue"),
      },

      {
        path: "kabupatens",
        name: "kabupatens-list",
        component: () => import("@/pages/KabupatenList.vue"),
        meta: { permission: "master-data.wilayah.manage" },
      },
      {
        path: "kabupatens/create",
        name: "kabupatens-create",
        component: () => import("@/pages/KabupatenCreate.vue"),
        meta: { permission: "master-data.wilayah.manage" },
      },
      {
        path: "kabupatens/:id/edit",
        name: "kabupatens-edit",
        component: () => import("@/pages/KabupatenEdit.vue"),
        meta: { permission: "master-data.wilayah.manage" },
      },
      {
        path: "master-address",
        name: "master-address",
        component: () => import("@/pages/MasterAddress/Index.vue"),
        meta: { permission: "master-data.address.view" },
      },
      {
        path: "customers",
        name: "customers-list",
        component: () => import("@/pages/Customer/Index.vue"),
        meta: { permission: "customer.viewOwn" },
      },
      {
        path: "customers/create",
        name: "customers-create",
        component: () => import("@/pages/Customer/Form.vue"),
        meta: { permission: "customer.manage" },
      },
      {
        path: "customers/:id/edit",
        name: "customers-edit",
        component: () => import("@/pages/Customer/Form.vue"),
        meta: { permission: "customer.manage" },
      },
      {
        path: "customers/:id",
        name: "customer-detail",
        component: () => import("@/pages/Customer/Detail.vue"),
        props: true,
        meta: { permission: "customer.viewOwn" },
      },
      {
        path: "vendors",
        name: "vendors-list",
        component: () => import("@/pages/MasterData/Vendor/Index.vue"),
        meta: { permission: "master-data.view" },
      },
      {
        path: "vendors/create",
        name: "vendors-create",
        component: () => import("@/pages/MasterData/Vendor/Form.vue"),
        meta: { breadcrumbTitle: "Tambah Vendor" },
      },
      {
        path: "vendors/:id/edit",
        name: "vendors-edit",
        component: () => import("@/pages/MasterData/Vendor/Form.vue"),
        meta: { breadcrumbTitle: "Edit Vendor" },
      },
      {
        path: "terminals",
        name: "terminals-list",
        component: () => import("@/pages/MasterData/Terminal/Index.vue"),
        meta: { permission: "master-data.view" },
      },
      {
        path: "approval-templates",
        name: "approval-templates",
        component: () =>
          import("@/pages/MasterData/ApprovalTemplate/Index.vue"),
        meta: { permission: "approval-template.manage" },
      },
      {
        path: "approval-templates/create",
        name: "approval-templates-create",
        component: () => import("@/pages/MasterData/ApprovalTemplate/Form.vue"),
        meta: {
          permission: "approval-template.manage",
          breadcrumbTitle: "Tambah Approval Template",
        },
      },
      {
        path: "approval-templates/:id/edit",
        name: "approval-templates-edit",
        component: () => import("@/pages/MasterData/ApprovalTemplate/Form.vue"),
        meta: {
          permission: "approval-template.manage",
          breadcrumbTitle: "Edit Approval Template",
        },
      },
      {
        path: "customer-document-types",
        name: "customer-document-types",
        component: () =>
          import("@/pages/MasterData/CustomerDocumentType/Index.vue"),
        meta: { permission: "master-data.customer-document-type.manage" },
      },
      {
        path: "customer-migration",
        name: "customer-migration",
        component: () => import("@/pages/Admin/CustomerMigration/Index.vue"),
        meta: { permission: "admin.customer-migration.manage" },
      },

      {
        path: "vendor-pos",
        name: "vendor-pos-list",
        title: "Po Supplier",
        component: () => import("@/pages/Transactions/PoSupplier/Index.vue"),
        meta: { title: "Po Supplier", permission: "po-supplier.manage" },
      },
      {
        path: "vendor-pos/create",
        name: "vendor-pos-create",
        component: () => import("@/pages/Transactions/PoSupplier/Form.vue"),
        meta: { permission: "po-supplier.manage" },
      },
      {
        path: "vendor-pos/:id/edit",
        name: "vendor-pos-edit",
        component: () => import("@/pages/Transactions/PoSupplier/Form.vue"),
        meta: { permission: "po-supplier.manage" },
      },
      {
        path: "vendor-pos/:id",
        name: "vendor-pos-detail",
        component: () => import("@/pages/Transactions/PoSupplier/Detail.vue"),
        meta: { permission: "po-supplier.manage" },
      },
      {
        path: "vendor-pos/:id/receive",
        name: "vendor-pos-receive",
        component: () => import("@/pages/Transactions/PoSupplier/Receive.vue"),
        meta: { permission: "po-supplier.manage" },
      },

      {
        path: "good-receipt",
        name: "good-receipt-list",
        component: () => import("@/pages/Transactions/GoodReceipt/Index.vue"),
        meta: { permission: "good-receipt.manage" },
      },

      {
        path: "/po-verification",
        name: "po-verification-list",
        component: () => import("@/pages/Verification/PoSupplier/Index.vue"),
        meta: { permission: "verification.po-supplier" },
      },

      {
        path: "/po-verification/:id",
        name: "po-verification-detail",
        component: () => import("@/pages/Verification/PoSupplier/Detail.vue"),
        props: true,
      },

      {
        path: "/stock-inventory",
        name: "StockInventory",
        component: () => import("@/pages/Inventory/Index.vue"),
        meta: { permission: "inventory.view" },
      },
      {
        path: "/penawarans",
        name: "penawarans-list",
        component: () => import("@/pages/Penawaran/Index.vue"),
        meta: { brand: "tds", permission: "penawaran.viewOwn" },
      },
      {
        path: "/penawarans-proenergi",
        name: "penawarans-list-proenergi",
        component: () => import("@/pages/Penawaran/Index.vue"),
        meta: { brand: "proenergi", permission: "penawaran.proenergi.manage" },
      },
      {
        path: "/penawarans/create",
        name: "penawarans-create",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "tds", permission: "penawaran.manage" },
      },
      {
        path: "/penawarans-proenergi/create",
        name: "penawarans-create-proenergi",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "proenergi", permission: "penawaran.proenergi.manage" },
      },

      {
        path: "/penawarans/:id/edit",
        name: "penawarans-edit",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "tds", permission: "penawaran.manage" },
      },

      {
        path: "/penawarans-proenergi/:id/edit",
        name: "penawarans-edit-proenergi",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "proenergi", permission: "penawaran.proenergi.manage" },
      },
      {
        path: "/penawarans/:id",
        name: "penawarans-detail",
        component: () => import("@/pages/Penawaran/Detail.vue"),
        meta: { brand: "tds", permission: "penawaran.viewOwn" },
      },
      {
        path: "/penawarans-proenergi/:id",
        name: "penawarans-detail-proenergi",
        component: () => import("@/pages/Penawaran/Detail.vue"),
        meta: { brand: "proenergi", permission: "penawaran.proenergi.manage" },
      },

      {
        path: "/jenis-produks",
        name: "jenis-produk-list",
        component: () => import("@/pages/MasterData/JenisProduk/Index.vue"),
        meta: { permission: "master-data.view" },
      },
      {
        path: "/transportir",
        name: "transportir-list",
        component: () => import("@/pages/TransportirList.vue"),
        meta: { permission: "logistik.master.manage" },
      },
      {
        path: "/transportirs/create",
        name: "transportirs-create",
        component: () => import("@/pages/TransportirForm.vue"),
      },
      {
        path: "/transportirs/:id/edit",
        name: "transportirs-edit",
        component: () => import("@/pages/TransportirForm.vue"),
      },
      {
        path: "/personnels",
        name: "personnel-list",
        component: () => import("@/pages/PersonnelList.vue"),
        meta: { permission: "logistik.master.manage" },
      },
      {
        path: "/personnels/create",
        name: "personnels-create",
        component: () => import("@/pages/PersonnelForm.vue"),
      },
      {
        path: "/personnels/:id/edit",
        name: "personnels-edit",
        component: () => import("@/pages/PersonnelForm.vue"),
      },
      {
        path: "/volumes",
        name: "volumes-list",
        component: () => import("@/pages/VolumeList.vue"),
        meta: { permission: "logistik.master.manage" },
      },
      {
        path: "/volumes/create",
        name: "volumes-create",
        component: () => import("@/pages/VolumeForm.vue"),
      },
      {
        path: "/volumes/:id/edit",
        name: "volumes-edit",
        component: () => import("@/pages/VolumeForm.vue"),
      },
      {
        path: "/wilayah-angkut",
        name: "wilayah-angkut-list",
        component: () => import("@/pages/WilayahAngkutList.vue"),
        meta: { permission: "logistik.master.manage" },
      },

      {
        path: "/wilayah-angkut/create",
        name: "wilayah-angkut-create",
        component: () => import("@/pages/WilayahAngkutForm.vue"),
      },

      {
        path: "/wilayah-angkut/:id/edit",
        name: "wilayah-angkut-edit",
        component: () => import("@/pages/WilayahAngkutForm.vue"),
        props: true,
      },

      {
        path: "/kapals",
        name: "kapals-list",
        component: () => import("@/pages/KapalList.vue"),
        meta: { title: "List Kapal", permission: "logistik.master.manage" },
      },
      {
        path: "/kapals/create",
        name: "kapals-create",
        component: () => import("@/pages/KapalForm.vue"),
        meta: { title: "Tambah Kapal" },
      },
      {
        path: "/kapals/:id/edit",
        name: "kapals-edit",
        component: () => import("@/pages/KapalForm.vue"),
        meta: { title: "Edit Kapal" },
      },
      {
        path: "/ongkos-kapal",
        name: "ongkos-kapal-list",
        component: () => import("@/pages/OngkosKapalList.vue"),
        meta: { permission: "logistik.master.manage" },
      },
      {
        path: "/ongkos-kapal/create",
        name: "ongkos-kapal-create",
        component: () => import("@/pages/OngkosKapalForm.vue"),
      },
      {
        path: "/ongkos-kapal/:id/edit",
        name: "ongkos-kapal-edit",
        component: () => import("@/pages/OngkosKapalForm.vue"),
        props: true,
      },
      {
        path: "/trucks",
        name: "trucks-list",
        component: () => import("@/pages/TruckList.vue"),
        meta: { permission: "logistik.master.manage" },
      },
      {
        path: "/trucks/create",
        name: "trucks-create",
        component: () => import("@/pages/TruckForm.vue"),
      },
      {
        path: "/trucks/:id/edit",
        name: "trucks-edit",
        component: () => import("@/pages/TruckForm.vue"),
        props: true,
      },
      {
        path: "/oa-trucks",
        name: "oa-trucks-list",
        component: () => import("@/pages/OaTruckList.vue"),
        props: true,
        meta: { permission: "logistik.master.manage" },
      },
      {
        path: "/oa-trucks/create",
        name: "oa-trucks-create",
        component: () => import("@/pages/OaTruckForm.vue"),
        props: true,
      },
      {
        path: "/oa-trucks/:id/edit",
        name: "oa-trucks-edit",
        component: () => import("@/pages/OaTruckForm.vue"),
        props: true,
      },

      {
        path: "/penawarans/verifikasi",
        name: "penawarans-verifikasi",
        component: () => import("@/pages/Verification/Penawaran/Index.vue"),
        meta: {
          brand: "reguler",
          permission: "verification.quotation",
        },
      },
      {
        path: "/penawarans-proenergi/verifikasi",
        name: "penawarans-verifikasi-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Index.vue"),
        meta: {
          brand: "proenergi",
          permission: "penawaran.proenergi.verify",
        },
      },
      {
        path: "/penawarans/:id/verifikasi",
        name: "penawarans-verifikasi-detail",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: { role: "bm", brand: "reguler", permission: "verification.quotation" },
      },
      {
        path: "/penawarans-proenergi/:id/verifikasi",
        name: "penawarans-verifikasi-detail-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: { role: "bm", brand: "proenergi", permission: "penawaran.proenergi.verify-bm" },
      },
      {
        path: "/penawarans/verifikasi/om/:id",
        name: "penawarans-verifikasi-om-detail",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: {
          role: "om",
          brand: "reguler",
          permission: "verification.quotation",
        },
      },
      {
        path: "/penawarans-proenergi/verifikasi/om/:id",
        name: "penawarans-verifikasi-om-detail-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: { role: "om", brand: "proenergi", permission: "penawaran.proenergi.verify-om" },
      },

      {
        path: "/po-customer/create",
        name: "penawarans-po",
        component: () => import("@/pages/PenawaranCustomerPO.vue"),
        meta: { permission: "penawaran.manage" },
      },

      {
        path: "/po-customers",
        name: "po-customers-index",
        component: () => import("@/pages/PoCustomersIndex.vue"),
      },

      {
        path: "/logistik/lcrs",
        name: "logistik-lcrs",
        component: () => import("@/pages/LogistikLcrList.vue"),
        meta: { permission: "logistik.lcr.verify" },
      },
      {
        path: "/logistik/lcrs/:id",
        name: "logistik-lcr-detail",
        component: () => import("@/pages/LogistikLcrDetail.vue"),
        props: true,
      },
      {
        path: "/customer-verifications",
        name: "customer-verifications",
        component: () => import("@/pages/CustomerVerification/Index.vue"),
        meta: { permission: "customer.viewOwn" },
      },
      {
        path: "/link-customers",
        name: "link-customers",
        component: () => import("@/pages/ListLinkCustomer.vue"),
        meta: { permission: "customer.manage" },
      },
      {
        path: "/admin/review-data-customer",
        name: "review-data-customer-admin",
        component: () =>
          import("@/pages/CustomerVerification/AdminFinance/Index.vue"),
        meta: { permission: "verification.customer" },
      },
      {
        path: "/admin/review-data-customer/:id",
        name: "review-data-customer-admin-detail",
        component: () =>
          import("@/pages/CustomerVerification/AdminFinance/Verify.vue"),
        props: true,
        meta: { permission: "verification.customer" },
      },
      {
        path: "/sales-confirmations",
        name: "sales-confirmations",
        component: () => import("@/pages/SalesConfirmationIndex.vue"),
        meta: { permission: "sales-confirmation.manage" },
      },
      {
        path: "/sales-confirmations/:id",
        name: "sales-confirmations-detail",
        component: () => import("@/pages/SalesConfirmationDetail.vue"),
      },
      {
        path: "/sales-confirmations/bm",
        name: "sales-confirmations-bm",
        component: () => import("@/pages/SalesConfirmationBM.vue"),
      },
      {
        path: "/sales-confirmations/bm/:id",
        name: "sales-confirmations-bm-detail",
        component: () => import("@/pages/SalesConfirmationBMDetail.vue"),
      },

      {
        path: "/sales-confirmations/bm/:id/po",
        name: "sales-confirmations-bm-detail-po",
        component: () => import("@/pages/SalesConfirmationBMDetailClassic.vue"),
        props: true,
      },

      {
        path: "/po-customer-plan/:id",
        name: "po-customer-plan",
        component: () => import("@/pages/PoCustomerPlan.vue"),
        props: true,
      },

      {
        path: "/logistics/delivery-plan",
        name: "logistics-delivery-plan",
        component: () => import("@/pages/DeliveryPlanList.vue"),
        meta: { title: "Delivery Plan", permission: "logistik.master.manage" },
      },

      {
        path: "/procurement/delivery-requests",
        name: "procurement-delivery-requests",
        component: () => import("@/pages/Delivery/Index.vue"),
        meta: {
          title: "Delivery Request",
          permission: "delivery-request.manage",
        },
      },
      {
        path: "/procurement/delivery-requests/:id",
        name: "procurement-dr-detail",
        component: () => import("@/pages/DeliveryRequestDetail.vue"),
        meta: {
          breadcrumbTitle: "Detail Delivery Request",
          permission: "delivery-request.manage",
        },
        props: true,
      },

      {
        path: "/monitoring/app-logs",
        name: "monitoring-app-logs",
        component: () => import("@/pages/Monitoring/Logs/Index.vue"),
        meta: {
          title: "Application Logs",
          permission: "admin.monitoring.view",
        },
      },

      // ... child routes lain ...
      {
        path: "testing-page",
        name: "testing-page",
        component: () => import("@/pages/Testing/Index.vue"),
      },
    ],
  },
  { path: "/:catchAll(.*)", redirect: "/login" },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

let shouldShowRouteLoading = false;

function getRouteLoadingScope(path: string) {
  const segments = path.replace(/\/+$/, "").split("/").filter(Boolean);

  if (segments.length === 0) {
    return "/";
  }

  const lastSegment = segments[segments.length - 1];
  const previousSegment = segments[segments.length - 2];

  if (lastSegment === "create") {
    segments.pop();
  }

  if (lastSegment === "edit" && previousSegment) {
    segments.pop();
    segments.pop();
  }

  return `/${segments.join("/")}`;
}

router.beforeEach(async (to, from, next) => {
  shouldShowRouteLoading =
    !from.name ||
    getRouteLoadingScope(to.path) !== getRouteLoadingScope(from.path);

  if (shouldShowRouteLoading) {
    startRouteLoading();
  }

  const token = localStorage.getItem("access_token");
  const auth = useAuthStore();

  // Isi user kalau ada token tapi state kosong
  if (token && !auth.user) {
    try {
      await auth.fetchUser();
    } catch (e) {
      /* optional: handle */
    }
  }

  // Belum login → redirect ke login
  if (
    !token &&
    to.name !== "login" &&
    to.name !== "two-factor" &&
    to.name !== "customer-onboarding" &&
    to.name !== "forgot-password"
  ) {
    // stop lebih cepat saat redirect agar tidak menggantung
    stopRouteLoading(150);
    return next({ name: "login", query: { logged_out: "1" } });
  }

  // Token ada tapi fetchUser() gagal (401 atau non-401) → auth.user tetap null.
  // Jangan biarkan navigasi lolos dengan user null (topbar akan fallback ke "Guest").
  // Skip kalau forceLogout() sedang menangani 401 yang sama, supaya tidak double-redirect.
  if (
    !auth.user &&
    to.name !== "login" &&
    to.name !== "two-factor" &&
    to.name !== "customer-onboarding" &&
    to.name !== "forgot-password" &&
    !auth.isForceLoggingOut
  ) {
    auth.clear();
    stopRouteLoading(150);
    return next({ name: "login", query: { logged_out: "1" } });
  }

  // Sudah login tapi mencoba akses halaman guest-only → redirect ke dashboard
  if (token && to.meta.guestOnly) {
    stopRouteLoading(150);
    return next({ name: "dashboard-overview-1" });
  }

  // Cek permission
  const requiredPermission = to.meta.permission;
  if (requiredPermission && !auth.can(requiredPermission)) {
    stopRouteLoading(150);
    return next({ name: "dashboard-overview-1" });
  }

  next();
});

// Route selesai → matikan dengan min visible 250ms
router.afterEach(() => {
  if (shouldShowRouteLoading) {
    stopRouteLoading(250);
  }

  shouldShowRouteLoading = false;
});

router.onError(() => {
  stopRouteLoading(150);
});

export default router;
