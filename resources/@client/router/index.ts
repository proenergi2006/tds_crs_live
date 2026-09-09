import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

import { startRouteLoading, stopRouteLoading } from "@/utils/routeLoading";

declare module "vue-router" {
  interface RouteMeta {
    title?: string;
    breadcrumbTitle?: string;
    brand?: string;
    role?: string;
    permission?: string | string[];
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
    path: "/verifikasi-penawaran/:token",
    name: "penawaran-verification",
    component: () => import("@/pages/PenawaranVerification/Index.vue"),
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
        path: "product-prices",
        name: "product-prices",
        component: () => import("@/pages/MasterData/ProductPrice/Index.vue"),
        meta: { permission: "price-period.view" },
      },
      {
        path: "product-prices/create",
        name: "product-prices-create",
        component: () => import("@/pages/MasterData/ProductPrice/Form.vue"),
        meta: {
          permission: "price-period.manage",
          breadcrumbTitle: "Tambah Harga Produk",
        },
      },
      {
        path: "product-prices/:id/edit",
        name: "product-prices-edit",
        component: () => import("@/pages/MasterData/ProductPrice/Form.vue"),
        meta: {
          permission: "price-period.manage",
          breadcrumbTitle: "Edit Harga Produk",
        },
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
        meta: { permission: "verification.po-supplier" },
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
        meta: { brand: "proenergi", permission: "penawaran.proenergi.viewOwn" },
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
        meta: { brand: "proenergi", permission: "penawaran.proenergi.viewOwn" },
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
          brand: "tds",
          permission: "penawaran.verify",
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
        path: "/penawarans/verifikasi/bm/:id",
        name: "penawarans-verifikasi-bm-detail",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: {
          role: "bm",
          brand: "tds",
          permission: ["penawaran.verify", "penawaran.verify-bm"],
        },
      },
      {
        path: "/penawarans-proenergi/verifikasi/bm/:id",
        name: "penawarans-verifikasi-bm-detail-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: {
          role: "bm",
          brand: "proenergi",
          permission: "penawaran.proenergi.verify-bm",
        },
      },
      {
        path: "/penawarans/verifikasi/om/:id",
        name: "penawarans-verifikasi-om-detail",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: {
          role: "om",
          brand: "tds",
          permission: ["penawaran.verify", "penawaran.verify-om"],
        },
      },
      {
        path: "/penawarans-proenergi/verifikasi/om/:id",
        name: "penawarans-verifikasi-om-detail-proenergi",
        component: () => import("@/pages/Verification/Penawaran/Detail.vue"),
        meta: {
          role: "om",
          brand: "proenergi",
          permission: "penawaran.proenergi.verify-om",
        },
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
        meta: { permission: "logistik.lcr.verify" },
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

      {
        path: "testing-page",
        name: "testing-page",
        component: () => import("@/pages/Testing/Index.vue"),
      },

      {
        path: "testing-page-2",
        name: "testing-page-2",
        component: () => import("@/pages/Tooltip.vue"),
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

  if (token && !auth.user) {
    try {
      await auth.fetchUser();
    } catch (e) {
      // noop
    }
  }

  if (
    !token &&
    to.name !== "login" &&
    to.name !== "two-factor" &&
    to.name !== "customer-onboarding" &&
    to.name !== "penawaran-verification" &&
    to.name !== "forgot-password"
  ) {
    stopRouteLoading(150);
    return next({ name: "login", query: { logged_out: "1" } });
  }

  if (
    !auth.user &&
    to.name !== "login" &&
    to.name !== "two-factor" &&
    to.name !== "customer-onboarding" &&
    to.name !== "penawaran-verification" &&
    to.name !== "forgot-password" &&
    !auth.isForceLoggingOut
  ) {
    auth.clear();
    stopRouteLoading(150);
    return next({ name: "login", query: { logged_out: "1" } });
  }

  if (token && to.meta.guestOnly) {
    stopRouteLoading(150);
    return next({ name: "dashboard-overview-1" });
  }

  const requiredPermission = to.meta.permission;
  const required = Array.isArray(requiredPermission)
    ? requiredPermission
    : [requiredPermission];
  if (requiredPermission && !required.every((p) => auth.can(p))) {
    stopRouteLoading(150);
    return next({ name: "dashboard-overview-1" });
  }

  next();
});

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
