import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

import { startRouteLoading, stopRouteLoading } from "@/utils/routeLoading";

const routes = [
  { path: "/", redirect: "/login" },
  {
    path: "/login",
    name: "login",
    meta: { title: "Login" },
    component: () => import("@/pages/Login.vue"),
  },

  {
    path: "/two-factor",
    name: "two-factor",
    component: () => import("@/pages/TwoFactor.vue"),
  },

  {
    path: "/forgot-password",
    name: "forgot-password",
    component: () => import("@/pages/ForgotPassword.vue"),
  },

  {
    path: "/app",
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
        component: () => import("@/pages/RoleOverview.vue"),
        meta: { roles: [1] },
      },
      {
        path: "users",
        name: "users",
        component: () => import("@/pages/Users.vue"),
        meta: { roles: [1] },
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
        meta: { roles: [1] },
      },
      {
        path: "profile-overview-3",
        name: "profile-overview-3",
        component: () => import("@/pages/ProfileOverview3.vue"),
        meta: { roles: [1] },
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
        meta: { roles: [2] },
      },
      {
        path: "satuan",
        name: "satuan",
        component: () => import("@/pages/MasterData/Satuan/Index.vue"),
        meta: { roles: [2, 5] },
      },
      {
        path: "ukuran",
        name: "ukuran",
        component: () => import("@/pages/MasterData/Ukuran/Index.vue"),
        meta: { roles: [2, 5] },
      },
      {
        path: "produks-list",
        name: "produks-list",
        component: () => import("@/pages/MasterData/Produk/Index.vue"),
        meta: { roles: [2, 5] },
      },
      {
        path: "produk-hargas",
        name: "produk-hargas",
        component: () => import("@/pages/MasterData/HargaProduk/Index.vue"),
        meta: { roles: [2, 5, 8] },
      },
      {
        path: "produk-hargas/:id/detail",
        name: "produk-hargas-detail",
        component: () => import("@/pages/MasterData/HargaProduk/Detail.vue"),
        meta: { breadcrumbTitle: "Detail Harga Produk", roles: [2, 5, 8] },
      },
      {
        path: "produk-hargas/create",
        name: "produk-hargas-create",
        component: () => import("@/pages/MasterData/HargaProduk/Form.vue"),
        meta: {
          roles: [5],
          breadcrumbTitle: "Tambah Harga Produk",
        },
      },
      {
        path: "produk-hargas/:id/edit",
        name: "produk-hargas-edit",
        component: () => import("@/pages/MasterData/HargaProduk/Form.vue"),
        meta: {
          roles: [2, 5, 8],
          breadcrumbTitle: "Edit Harga Produk",
        },
      },
      {
        path: "attachment-harga-dasar",
        name: "attachment-harga-dasar-list",
        component: () => import("@/pages/AttachmentHargaDasarList.vue"),
        meta: { roles: [2, 5, 8] },
      },
      {
        path: "attachment-harga-dasar/create",
        name: "attachment-harga-dasar-create",
        component: () => import("@/pages/AttachmentHargaDasarCreate.vue"),
        meta: { roles: [2, 5, 8] },
      },
      {
        path: "attachment-harga-dasar/:id/edit",
        name: "attachment-harga-dasar-edit",
        component: () => import("@/pages/AttachmentHargaDasarEdit.vue"),
        meta: { roles: [2, 5, 8] },
      },
      {
        path: "provinsis",
        name: "provinsi-list",
        component: () => import("@/pages/ProvinsiList.vue"),
        meta: { roles: [1, 2, 8] },
      },
      {
        path: "provinsis/create",
        name: "provinsi-create",
        component: () => import("@/pages/ProvinsiCreate.vue"),
        meta: { roles: [1, 2, 8] },
      },
      {
        path: "provinsis/:id/edit",
        name: "provinsi-edit",
        component: () => import("@/pages/ProvinsiEdit.vue"),
        meta: { roles: [1, 2, 8] },
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
        meta: { roles: [1, 2, 8] },
      },
      {
        path: "kabupatens/create",
        name: "kabupatens-create",
        component: () => import("@/pages/KabupatenCreate.vue"),
        meta: { roles: [1, 2, 8] },
      },
      {
        path: "kabupatens/:id/edit",
        name: "kabupatens-edit",
        component: () => import("@/pages/KabupatenEdit.vue"),
        meta: { roles: [1, 2, 8] },
      },
      {
        path: "customers",
        name: "customers-list",
        component: () => import("@/pages/Customer/Index.vue"),
        meta: { roles: [4, 12] },
      },
      {
        path: "customers-proenergi",
        name: "customers-list-proenergi",
        component: () => import("@/pages/Customer/IndexProenergi.vue"),
        meta: { roles: [13, 14] },
      },
      {
        path: "customers/create",
        name: "customers-create",
        component: () => import("@/pages/Customer/Form.vue"),
        meta: { roles: [4, 12] },
      },
      {
        path: "customers-proenergi/create",
        name: "customers-create-proenergi",
        component: () => import("@/pages/Customer/Form.vue"),
      },
      {
        path: "customers/:id/edit",
        name: "customers-edit",
        component: () => import("@/pages/Customer/Form.vue"),
        meta: { roles: [4, 12] },
      },
      {
        path: "customers-proenergi/:id/edit",
        name: "customers-edit-proenergi",
        component: () => import("@/pages/Customer/Form.vue"),
      },
      {
        path: "vendors",
        name: "vendors-list",
        component: () => import("@/pages/MasterData/Vendor/Index.vue"),
        meta: { roles: [2, 5] },
      },
      {
        path: "vendors/create",
        name: "vendors-create",
        component: () => import("@/pages/MasterData/Vendor/Form.vue"),
        meta: { roles: [5], breadcrumbTitle: "Tambah Vendor" },
      },
      {
        path: "vendors/:id/edit",
        name: "vendors-edit",
        component: () => import("@/pages/MasterData/Vendor/Form.vue"),
        meta: { roles: [5], breadcrumbTitle: "Edit Vendor" },
      },
      {
        path: "terminals",
        name: "terminals-list",
        component: () => import("@/pages/MasterData/Terminal/Index.vue"),
        meta: { roles: [2, 5] },
      },

      {
        path: "vendor-pos",
        name: "vendor-pos-list",
        title: "Po Supplier",
        component: () => import("@/pages/Transactions/PoSupplier/Index.vue"),
        meta: { title: "Po Supplier", roles: [5] },
      },
      {
        path: "vendor-pos/create",
        name: "vendor-pos-create",
        component: () => import("@/pages/Transactions/PoSupplier/Form.vue"),
        meta: { roles: [5] },
      },
      {
        path: "vendor-pos/:id/edit",
        name: "vendor-pos-edit",
        component: () => import("@/pages/Transactions/PoSupplier/Form.vue"),
        meta: { roles: [5] },
      },
      {
        path: "vendor-pos/:id",
        name: "vendor-pos-detail",
        component: () => import("@/pages/Transactions/PoSupplier/Detail.vue"),
        meta: { roles: [5] },
      },
      {
        path: "vendor-pos/:id/receive",
        name: "vendor-pos-receive",
        component: () => import("@/pages/Transactions/PoSupplier/Receive.vue"),
        meta: { roles: [5] },
      },

      {
        path: "good-receipt",
        name: "good-receipt-list",
        component: () => import("@/pages/Transactions/GoodReceipt/Index.vue"),
        meta: { roles: [5] },
      },

      {
        path: "/po-verification",
        name: "po-verification-list",
        component: () => import("@/pages/Verification/PoSupplier/Index.vue"),
        meta: { roles: [2, 3] },
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
        meta: { roles: [5] },
      },
      {
        path: "/penawarans",
        name: "penawarans-list",
        component: () => import("@/pages/Penawaran/Index.vue"),
        meta: { brand: "tds", roles: [4, 12] },
      },
      {
        path: "/penawarans-proenergi",
        name: "penawarans-list-proenergi",
        component: () => import("@/pages/Penawaran/Index.vue"),
        meta: { brand: "proenergi", roles: [13, 14] },
      },
      {
        path: "/penawarans/create",
        name: "penawarans-create",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "tds", roles: [4, 12] },
      },
      {
        path: "/penawarans-proenergi/create",
        name: "penawarans-create-proenergi",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "proenergi", roles: [13, 14] },
      },

      {
        path: "/penawarans/createlubricant",
        name: "penawarans-create-lubricant",
        component: () => import("@/pages/PenawaranFormLubricant.vue"),
      },

      {
        path: "/penawarans/:id/edit",
        name: "penawarans-edit",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "tds", roles: [4, 12] },
      },

      {
        path: "/penawarans-proenergi/:id/edit",
        name: "penawarans-edit-proenergi",
        component: () => import("@/pages/Penawaran/Form.vue"),
        meta: { brand: "proenergi", roles: [13, 14] },
      },
      {
        path: "/penawarans/:id",
        name: "penawarans-detail",
        component: () => import("@/pages/Penawaran/Detail.vue"),
        meta: { brand: "tds", roles: [4, 12] },
      },
      {
        path: "/penawarans-proenergi/:id",
        name: "penawarans-detail-proenergi",
        component: () => import("@/pages/Penawaran/Detail.vue"),
        meta: { brand: "proenergi", roles: [13, 14] },
      },

      {
        path: "/jenis-produks",
        name: "jenis-produk-list",
        component: () => import("@/pages/MasterData/JenisProduk/Index.vue"),
        meta: { roles: [2, 5] },
      },
      {
        path: "/transportir",
        name: "transportir-list",
        component: () => import("@/pages/TransportirList.vue"),
        meta: { roles: [7] },
      },
      {
        path: "/transportirs/create",
        name: "transportirs-create",
        component: () => import("@/pages/TransportirForm.vue"),
      },
      {
        path: "/transportirs/:id/edit",
        name: "transportirs-edit",
        component: () => import("@/pages/TransportirForm.vue"), // atau apapun komponen edit kamu
      },
      {
        path: "/personnels",
        name: "personnel-list",
        component: () => import("@/pages/PersonnelList.vue"),
        meta: { roles: [7] },
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
        meta: { roles: [7] },
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
        meta: { roles: [7] },
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
        meta: { title: "List Kapal", roles: [7] },
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
        meta: { roles: [7] },
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
        meta: { roles: [7] },
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
        meta: { roles: [7] },
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
        meta: { role: "bm", brand: "reguler", roles: [8] },
      },
      {
        path: "/penawarans-proenergi/verifikasi",
        name: "penawarans-verifikasi-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Index.vue"),
        meta: { role: "bm", brand: "proenergi", roles: [15] },
      },
      {
        path: "/penawarans/verifikasi/om",
        name: "penawarans-verifikasi-om",
        component: () => import("@/pages/Verification/Penawaran/Index.vue"),
        meta: { role: "om", brand: "reguler", roles: [2, 3, 10] },
      },
      {
        path: "/penawarans-proenergi/verifikasi/om",
        name: "penawarans-verifikasi-om-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Index.vue"),
        meta: { role: "om", brand: "proenergi", roles: [16] },
      },
      {
        path: "/penawarans/:id/verifikasi",
        name: "penawarans-verifikasi-detail",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: { role: "bm", brand: "reguler" },
      },
      {
        path: "/penawarans-proenergi/:id/verifikasi",
        name: "penawarans-verifikasi-detail-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: { role: "bm", brand: "proenergi" },
      },
      {
        path: "/penawarans/verifikasi/om/:id",
        name: "penawarans-verifikasi-om-detail",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: { role: "om", brand: "reguler", roles: [2, 3, 10] },
      },
      {
        path: "/penawarans-proenergi/verifikasi/om/:id",
        name: "penawarans-verifikasi-om-detail-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: { role: "om", brand: "proenergi" },
      },

      {
        path: "/po-customer/create",
        name: "penawarans-po",
        component: () => import("@/pages/PenawaranCustomerPO.vue"),
      },

      {
        path: "/po-customer/create",
        name: "penawarans-po",
        component: () => import("@/pages/PenawaranCustomerPO.vue"),
      },

      {
        path: "/po-customers",
        name: "po-customers-index",
        component: () => import("@/pages/PoCustomersIndex.vue"),
      },

      {
        path: "customer-lcrs",
        name: "lcr-list",

        component: () => import("@/pages/CustomerLcrList.vue"),
      },
      {
        path: "customer-lcrs/create",
        name: "lcr-create",
        component: () => import("@/pages/CustomerLcrForm.vue"),
      },
      {
        path: "customer-lcrs/:id/edit",
        name: "lcr-edit",
        component: () => import("@/pages/CustomerLcrForm.vue"),
      },

      {
        path: "/logistik/lcrs",
        name: "logistik-lcrs",
        component: () => import("@/pages/LogistikLcrList.vue"),
        meta: { roles: [6] },
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
        component: () => import("@/pages/CustomerVerificationList.vue"), // komponen list yg tadi
      },
      {
        path: "/link-customers",
        name: "link-customers",
        component: () => import("@/pages/ListLinkCustomer.vue"),
      },
      {
        path: "/verify/:token",
        name: "verify-customer",
        component: () => import("@/pages/CustomerUpdateForm.vue"),
      },
      {
        path: "/review-customer",
        name: "review-customer",
        component: () => import("@/pages/CustomerReviewList.vue"),
      },
      {
        path: "/review-data-customer",
        name: "review-data-customer",
        component: () => import("@/pages/ReviewDataCustomer.vue"),
        // bawa state tab/search/paging lewat query
      },

      {
        path: "/review-data-customer/:id",
        name: "review-customer-detail",
        component: () => import("@/pages/ReviewCustomerDetail.vue"),
        props: true,
      },
      {
        path: "/admin/review-data-customer",
        name: "review-data-customer-admin",
        component: () => import("@/pages/ReviewDataCustomerAdmin.vue"),
        meta: { roles: [9] },
      },
      {
        path: "/review/logistik",
        name: "review-data-customer-logistik",
        component: () => import("@/pages/ReviewDataCustomerLogistik.vue"),
        meta: { roles: [7] },
      },
      {
        path: "/review/logistik/:id",
        name: "review-customer-detail-logistik",
        component: () => import("@/pages//LogistikCustomerDetail.vue"),
      },
      {
        path: "/review/bm",
        name: "verify-data-customer-bm",
        component: () => import("@/pages/VerifyDataCustomerBM.vue"),
      },

      {
        path: "/review/bm/:id",
        name: "bm-customer-detail",
        props: true,
        component: () => import("@/pages/BMCustomerDetail.vue"),
      },

      // OM (list antrean + detail)
      {
        path: "/review/om",
        name: "verify-data-customer-om",
        component: () => import("@/pages/VerifyDataCustomerOM.vue"),
      },
      {
        path: "/review/om/:id",
        name: "om-customer-detail",
        props: true,
        component: () => import("@/pages/OMCustomerDetail.vue"),
      },

      {
        path: "/sales-confirmations",
        name: "sales-confirmations",
        component: () => import("@/pages/SalesConfirmationIndex.vue"),
        meta: { roles: [9] },
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
        path: "/sales-confirmations/bm/:id",
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
        meta: { title: "Delivery Plan", roles: [7] },
      },

      {
        path: "/procurement/delivery-requests",
        name: "procurement-delivery-requests",
        component: () => import("@/pages/Delivery/Index.vue"),
        meta: { title: "Delivery Request", roles: [5] },
      },
      {
        path: "/procurement/delivery-requests/:id",
        name: "procurement-dr-detail",
        component: () => import("@/pages/DeliveryRequestDetail.vue"),
        meta: { breadcrumbTitle: "Detail Delivery Request", roles: [5] },
        props: true,
      },

      {
        path: "/monitoring/app-logs",
        name: "monitoring-app-logs",
        component: () => import("@/pages/Monitoring/Logs/Index.vue"),
        // meta: { breadcrumbTitle: "Detail Delivery Request" },
        meta: { title: "Application Logs", roles: [1] },
      },

      // {
      //   path: '/po-verification/:id',
      //   name: 'po-verification-detail',
      //   component: () => import('@/pages/PoVerificationDetail.vue'),
      //   meta: { roles: ['CFO','CEO'] },
      //   props: true,
      // },

      // ... child routes lain ...
      {
        path: "testing-page",
        name: "testing-page",
        component: () => import("@/pages/Dropdown.vue"),
      },
      {
        path: "/testing-page",
        name: "testing-page-2",
        component: () => import("@/pages/Testing/Index.vue"),
      },
    ],
  },
  { path: "/:catchAll(.*)", redirect: "/login" },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
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
  if (!token && to.name !== "login" && to.name !== "two-factor") {
    // stop lebih cepat saat redirect agar tidak menggantung
    stopRouteLoading(150);
    return next({ name: "login", query: { logged_out: "1" } });
  }

  // Cek role
  const allowedRoles = to.meta.roles as number[] | undefined;
  if (allowedRoles && !allowedRoles.includes(auth.user?.id_role ?? -1)) {
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
