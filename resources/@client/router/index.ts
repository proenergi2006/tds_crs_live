import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import { startRouteLoading, stopRouteLoading } from '@/utils/routeLoading'

const routes = [
  { path: '/', redirect: '/login' },
  {
    path: '/login',
    name: 'login',
    meta: { title: 'Login' },
    component: () => import('@/pages/Login.vue'),
  },

  {
    path: '/two-factor',
    name: 'two-factor',
    component: () => import('@/pages/TwoFactor.vue'),
  },

  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/pages/ForgotPassword.vue'),
  },
  
  {
    path: '/app',
    component: () => import('@/themes/Layout.vue'),
    children: [
      {
        path: '',
        name: 'dashboard-overview-1',
        meta: { title: 'Dashboard Overview' },
        component: () => import('@/pages/DashboardOverview1.vue'),
        
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
        component: () => import("@/pages/Satuan.vue"),
        meta: { roles: [2, 5] },
      },
      {
        path: "ukuran",
        name: "ukuran",
        component: () => import("@/pages/Ukuran.vue"),
        meta: { roles: [2, 5] },
      },
      {
        path: 'produks-list',
        name: 'produks-list',
        component: () => import('@/pages/ProdukList.vue'),
        meta: { roles: [2, 5] },
      },
      {
        path: 'produks/create',
        name: 'produks-create',
        component: () => import('@/pages/ProdukCreate.vue'),
        meta: { roles: [2, 5] },
      },
      {
        path: 'produks/:id/edit',
        name: 'produks-edit',
        component: () => import('@/pages/ProdukEdit.vue'),
        meta: { roles: [2, 5] },
        props: true,
      },
      {
        path: 'produk-hargas',
        name: 'produk-hargas',
        component: () => import('@/pages/ProdukHargaList.vue'),
        // meta: { roles: [2, 5] },
      },
      {
        path: 'produk-hargas/create',
        name: 'produk-hargas-create',
        component: () => import('@/pages/ProdukHargaCreate.vue'),
        meta: { roles: [2, 5, 8] },
      },
      {
        path: 'produk-hargas/:id/edit',
        name: 'produk-hargas-edit',
        component: () => import('@/pages/ProdukHargaEdit.vue'),
        meta: { roles: [2, 5, 8] },
      },
      {
        path: 'attachment-harga-dasar',
        name: 'attachment-harga-dasar-list',
        component: () => import('@/pages/AttachmentHargaDasarList.vue'),
        meta: { roles: [2, 5, 8] },
      },
      {
        path: 'attachment-harga-dasar/create',
        name: 'attachment-harga-dasar-create',
        component: () => import('@/pages/AttachmentHargaDasarCreate.vue'),
        meta: { roles: [2,5,8] },
      },
      {
        path: 'attachment-harga-dasar/:id/edit',
        name: 'attachment-harga-dasar-edit',
        component: () => import('@/pages/AttachmentHargaDasarEdit.vue'),
        meta: { roles: [2,5,8] },
      },
      {
        path: 'provinsis',
        name: 'provinsi-list',
        component: () => import('@/pages/ProvinsiList.vue'),
        meta: { roles: [1,2,8] },
      },
      {
        path: 'provinsis/create',
        name: 'provinsi-create',
        component: () => import('@/pages/ProvinsiCreate.vue'),
        meta: { roles: [1,2,8] },
      },
      {
        path: 'provinsis/:id/edit',
        name: 'provinsi-edit',
        component: () => import('@/pages/ProvinsiEdit.vue'),
        meta: { roles: [1,2,8] },
      },
      {
        path: 'calendar',
        name: 'calendar',
        component: () => import('@/pages/Calendar.vue'),
       
      },

      {
        path: 'kabupatens',
        name: 'kabupatens-list',
        component: () => import('@/pages/KabupatenList.vue'),
        meta: { roles: [1,2,8] },
      },
      {
        path: 'kabupatens/create',
        name: 'kabupatens-create',
        component: () => import('@/pages/KabupatenCreate.vue'),
        meta: { roles: [1,2,8] },
      },
      {
        path: 'kabupatens/:id/edit',
        name: 'kabupatens-edit',
        component: () => import('@/pages/KabupatenEdit.vue'),
        meta: { roles: [1,2,8] },
      },
      {
        path: 'customers',
        name: 'customers-list',
        component: () => import('@/pages/CustomersList.vue'),
      
      },
      {
        path: 'customers/create',
        name: 'customers-create',
        component: () => import('@/pages/CustomersCreate.vue'),
        
      },
      {
        path: 'customers/:id/edit',
        name: 'customers-edit',
        component: () => import('@/pages/CustomersEdit.vue'),
       
      },
      {
        path: 'vendors',
        name: 'vendors-list',
        component: () => import('@/pages/VendorList.vue'),
      
      },
      {
        path: 'vendors/create',
        name: 'vendors-create',
        component: () => import('@/pages/VendorCreate.vue'),
        meta: { roles: [5] },
      },
      {
        path: 'vendors/:id/edit',
        name: 'vendors-edit',
        component: () => import('@/pages/VendorEdit.vue'),
        meta: { roles: [5] },
      },
      {
        path: 'terminals',
        name: 'terminals-list',
        component: () => import('@/pages/TerminalList.vue'),
        meta: { roles: [5] },
      },
      {
        path: 'terminals/create',
        name: 'terminals-create',
        component: () => import('@/pages/TerminalCreate.vue'),
        meta: { roles: [5] },
      },
      {
        path: 'terminals/:id/edit',
        name: 'terminals-edit',
        component: () => import('@/pages/TerminalEdit.vue'),
        meta: { roles: [5] },
        
      },

      {
        path: 'vendor-pos',
        name: 'vendor-pos-list',
        component: () => import('@/pages/VendorPoList.vue'),
        meta: { roles: [5] },
      }, 
      {
        path: 'vendor-pos/create',
        name: 'vendor-pos-create',
        component: () => import('@/pages/VendorPoCreate.vue'),
        meta: { roles: [5] },
      },
      {
        path: 'vendor-pos/:id/edit',
        name: 'vendor-pos-edit',
        component: () => import('@/pages/VendorPoEdit.vue'),
        meta: { roles: [5] },
      },
      {
        path: 'vendor-pos/:id',
        name: 'vendor-pos-detail',
        component: () => import('@/pages/VendorPoDetail.vue'),
        meta: { roles: [5] },
      },
      {
        path: '/po-verification',
        name: 'po-verification-list',
        component: () => import('@/pages/PoVerificationList.vue'),

      },

      {
        path: '/po-verification/:id',
        name: 'po-verification-detail',
        component: () => import('@/pages/PoVerificationDetail.vue'),
        props: true,
      },

      {
        path: '/vendor-pos/:id/receive',
        name: 'receive-item-list',
        component: () => import('@/pages/ReceiveItem.vue'),
        meta: { roles: [5] },
      },

      {
        path: '/stock-inventory',
        name: 'StockInventory',
        component: () => import('@/pages/StockInventory.vue'),
      },
      {
        path: '/penawarans',
        name: 'penawarans-list',
        component: () => import('@/pages/PenawaranList.vue'),
      },
      {
        path: '/penawarans/create',
        name: 'penawarans-create',
        component: () => import('@/pages/PenawaranForm.vue'),
      },
      {
        path: '/penawarans/createlubricant',
        name: 'penawarans-create-lubricant',
        component: () => import('@/pages/PenawaranFormLubricant.vue'),
      },

      {
        path: '/penawarans/:id/edit',
        name: 'penawarans-edit',
        component: () => import('@/pages/PenawaranForm.vue'),
      },
      {
        path: '/penawarans/:id',
        name: 'penawarans-detail',
        component: () => import('@/pages/PenawaranDetail.vue'),
      },

      {
        path: '/jenis-produks',
        name: 'jenis-produk-list',
        component: () => import('@/pages/JenisProdukList.vue')
      },
      {
        path: '/jenis-produks/create',
        name: 'jenis-produk-create',
        component: () => import('@/pages/JenisProdukForm.vue')
      },
      {
        path: '/jenis-produks/:id/edit',
        name: 'jenis-produk-edit',
        component: () => import('@/pages/JenisProdukForm.vue')
      },
      {
        path: '/jenis-produks/:id',
        name: 'jenis-produk-detail',
        component: () => import('@/pages/JenisProdukDetail.vue')
      },
      {
        path: '/transportir',
        name: 'transportir-list',
        component: () => import('@/pages/TransportirList.vue')
      },
      {
        path: '/transportirs/create',
        name: 'transportirs-create',
        component: () => import('@/pages/TransportirForm.vue'),
      },
      {
        path: '/transportirs/:id/edit',
        name: 'transportirs-edit',
        component: () => import('@/pages/TransportirForm.vue') // atau apapun komponen edit kamu
      },
      {
        path: '/personnels',
        name: 'personnel-list',
        component: () => import('@/pages/PersonnelList.vue')
      },
      {
        path: '/personnels/create',
        name: 'personnels-create',
        component: () => import('@/pages/PersonnelForm.vue')
      },
      {
        path: '/personnels/:id/edit',
        name: 'personnels-edit',
        component: () => import('@/pages/PersonnelForm.vue')
      },
      {
        path: '/volumes',
        name: 'volumes-list',
        component: () => import('@/pages/VolumeList.vue')
      },
      {
        path: '/volumes/create',
        name: 'volumes-create',
        component: () => import('@/pages/VolumeForm.vue')
      },
      {
        path: '/volumes/:id/edit',
        name: 'volumes-edit',
        component: () => import('@/pages/VolumeForm.vue')
      },
      {
        path: '/wilayah-angkut',
        name: 'wilayah-angkut-list',
        component: () => import('@/pages/WilayahAngkutList.vue'),
      },
      
      {
        path: '/wilayah-angkut/create',
        name: 'wilayah-angkut-create',
        component: () => import('@/pages/WilayahAngkutForm.vue'),
      },
      
      {
        path: '/wilayah-angkut/:id/edit',
        name: 'wilayah-angkut-edit',
        component: () => import('@/pages/WilayahAngkutForm.vue'),
        props: true
      },

      {
        path: '/kapals',
        name: 'kapals-list',
        component: () => import('@/pages/KapalList.vue'),
        meta: { title: 'List Kapal' }
      },
      {
        path: '/kapals/create',
        name: 'kapals-create',
        component: () => import('@/pages/KapalForm.vue'),
        meta: { title: 'Tambah Kapal' }
      },
      {
        path: '/kapals/:id/edit',
        name: 'kapals-edit',
        component: () => import('@/pages/KapalForm.vue'),
        meta: { title: 'Edit Kapal' }
      },
      {
        path: '/ongkos-kapal',
        name: 'ongkos-kapal-list',
        component: () => import('@/pages/OngkosKapalList.vue'),
      },
      {
        path: '/ongkos-kapal/create',
        name: 'ongkos-kapal-create',
        component: () => import('@/pages/OngkosKapalForm.vue'),
      },
      {
        path: '/ongkos-kapal/:id/edit',
        name: 'ongkos-kapal-edit',
        component: () => import('@/pages/OngkosKapalForm.vue'),
        props: true,
      },
      {
        path: '/trucks',
        name: 'trucks-list',
        component: () => import('@/pages/TruckList.vue'),
      },
      {
        path: '/trucks/create',
        name: 'trucks-create',
        component: () => import('@/pages/TruckForm.vue'),
      },
      {
        path: '/trucks/:id/edit',
        name: 'trucks-edit',
        component: () => import('@/pages/TruckForm.vue'),
        props: true,
      },
      {
        path: '/oa-trucks',
        name: 'oa-trucks-list',
        component: () => import('@/pages/OaTruckList.vue'),
        props: true,
      },
      {
        path: '/oa-trucks/create',
        name: 'oa-trucks-create',
        component: () => import('@/pages/OaTruckForm.vue'),
        props: true,
      },
      {
        path: '/oa-trucks/:id/edit',
        name: 'oa-trucks-edit',
        component: () => import('@/pages/OaTruckForm.vue'),
        props: true,
      },
      {
        path: '/penawarans/verifikasi',
        name: 'penawarans-verifikasi',
        component: () => import('@/pages/PenawaranVerifikasiList.vue'),
      },
      {
        path: '/penawarans/:id/verifikasi',
        name: 'penawarans-verifikasi-detail',
        component: () => import('@/pages/VerifikasiDetail.vue'), // Pastikan path sesuai
      },
      {
        path: '/penawarans/verifikasi/om',
        name: 'penawarans-verifikasi-om',
        component: () => import('@/pages/PenawaranVerifikasiListOm.vue'),
      },
      {
        path: '/penawarans/verifikasi/om/:id',
        name: 'penawarans-verifikasi-om-detail',
        component: () => import('@/pages/VerifikasiOmDetail.vue'),
      
      },
      {
        path: '/po-customer/create',
        name: 'penawarans-po',
        component: () => import('@/pages/PenawaranCustomerPO.vue'),
      },

      {
        path: '/po-customer/create',
        name: 'penawarans-po',
        component: () => import('@/pages/PenawaranCustomerPO.vue'),
      },

      {
        path: '/po-customers',
        name: 'po-customers-index',
        component: () => import('@/pages/PoCustomersIndex.vue'),
      },

      {
        path: 'customer-lcrs',
        name: 'lcr-list',
        
        component: () => import('@/pages/CustomerLcrList.vue'),
      },
      {
        path: 'customer-lcrs/create',
        name: 'lcr-create',
        component: () => import('@/pages/CustomerLcrForm.vue'),
      },
      {
        path: 'customer-lcrs/:id/edit',
        name: 'lcr-edit',
        component: () => import('@/pages/CustomerLcrForm.vue'),
      },

      {
        path: '/logistik/lcrs',
        name: 'logistik-lcrs',
        component: () => import('@/pages/LogistikLcrList.vue'),
      },
      {
        path: '/logistik/lcrs/:id',
        name: 'logistik-lcr-detail',
        component: () => import('@/pages/LogistikLcrDetail.vue'),
        props: true,
      },
      {
        path: '/customer-verifications',
        name: 'customer-verifications',
        component: () => import('@/pages/CustomerVerificationList.vue'), // komponen list yg tadi
      },
      {
        path: '/link-customers',
        name: 'link-customers',
        component: () => import('@/pages/ListLinkCustomer.vue'),
      },
      { path: '/verify/:token', 
        name: 'verify-customer', 
        component: () => import('@/pages/CustomerUpdateForm.vue') 
      },
      {
        path: '/review-customer',
        name: 'review-customer',
        component: () => import('@/pages/CustomerReviewList.vue') 
      },
      {
        path: '/review-data-customer',
        name: 'review-data-customer',
        component: () => import('@/pages/ReviewDataCustomer.vue'),
        // bawa state tab/search/paging lewat query
      },

      {
        path: '/review-data-customer/:id',
        name: 'review-customer-detail',
        component: () => import('@/pages/ReviewCustomerDetail.vue'),
        props: true,
      },
      {
        path: '/admin/review-data-customer',
        name: 'review-data-customer-admin',
        component: () => import('@/pages/ReviewDataCustomerAdmin.vue'),
      },
      {
        path: '/review/logistik',
        name: 'review-data-customer-logistik',
        component: () => import('@/pages/ReviewDataCustomerLogistik.vue'),
      },
      {
        path: '/review/logistik/:id',
        name: 'review-customer-detail-logistik',
        component: () => import('@/pages//LogistikCustomerDetail.vue'),
      },
       { 
         path: '/review/bm',
         name: 'verify-data-customer-bm', 
         component: () => import('@/pages/VerifyDataCustomerBM.vue') 
        },

        { 
          path: '/review/bm/:id', 
          name: 'bm-customer-detail', 
          props: true, 
          component: () => import('@/pages/BMCustomerDetail.vue') 
        },

        // OM (list antrean + detail)
{ 
  path: '/review/om', 
  name: 'verify-data-customer-om',
  component: () => import('@/pages/VerifyDataCustomerOM.vue')
 },
{ 
  path: '/review/om/:id', 
  name: 'om-customer-detail',
  props: true, 
  component: () => import('@/pages/OMCustomerDetail.vue')
 },

 {
  path: '/sales-confirmations',
  name: 'sales-confirmations',
  component: () => import('@/pages/SalesConfirmationIndex.vue')
  
},
{
  path: '/sales-confirmations/:id',
  name: 'sales-confirmations-detail',
  component: () => import('@/pages/SalesConfirmationDetail.vue')
},
{
  path: '/sales-confirmations/bm',
  name: 'sales-confirmations-bm',
  component: () => import('@/pages/SalesConfirmationBM.vue')
},
{
  path: '/sales-confirmations/bm/:id',
  name: 'sales-confirmations-bm-detail',
  component: () => import('@/pages/SalesConfirmationBMDetail.vue')
},

{
  path: '/sales-confirmations/bm/:id',
  name: 'sales-confirmations-bm-detail-po',
  component: () => import('@/pages/SalesConfirmationBMDetailClassic.vue'),
  props: true,
},

{
  path: '/po-customer-plan/:id',
  name: 'po-customer-plan',
  component: () => import('@/pages/PoCustomerPlan.vue'),
  props: true,
},

{
  path: '/logistics/delivery-plan',
  name: 'logistics-delivery-plan',
  component: () => import('@/pages/DeliveryPlanList.vue'),
  meta: { title: 'Delivery Plan' },
},

{
  path: '/procurement/delivery-requests',
  name: 'procurement-delivery-requests',
  component: () => import('@/pages/DeliveryRequestList.vue'),
  meta: { title: 'Delivery Request' }, // opsional role guard juga bisa ditambah
},
{
  path: '/procurement/delivery-requests/:id',
  name: 'procurement-dr-detail',
  component: () => import('@/pages/DeliveryRequestDetail.vue'),
  props: true,
}






      
      
      // {
      //   path: '/po-verification/:id',
      //   name: 'po-verification-detail',
      //   component: () => import('@/pages/PoVerificationDetail.vue'),
      //   meta: { roles: ['CFO','CEO'] },
      //   props: true,
      // },
      
      
      
      // ... child routes lain ...
    ],
  },
  { path: '/:catchAll(.*)', redirect: '/login' },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to, from, next) => {
  startRouteLoading()

  const token = localStorage.getItem('access_token')
  const auth  = useAuthStore()

  // Isi user kalau ada token tapi state kosong
  if (token && !auth.user) {
    try { await auth.fetchUser() } catch (e) { /* optional: handle */ }
  }

  // Belum login → redirect ke login
  if (!token && to.name !== 'login' && to.name !== 'two-factor') {
    // stop lebih cepat saat redirect agar tidak menggantung
    stopRouteLoading(150)
    return next({ name: 'login', query: { logged_out: '1' } })
  }

  // Cek role
  const allowedRoles = (to.meta.roles as number[] | undefined)
  if (allowedRoles && !allowedRoles.includes(auth.user?.id_role ?? -1)) {
    stopRouteLoading(150)
    return next({ name: 'dashboard-overview-1' })
  }

  next()
})

// Route selesai → matikan dengan min visible 250ms
router.afterEach(() => {
  stopRouteLoading(250)
})

router.onError(() => {
  stopRouteLoading(150)
})

export default router