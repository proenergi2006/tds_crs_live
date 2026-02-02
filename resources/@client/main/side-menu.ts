import { type Menu } from "@/stores/menu";
import { useAuthStore } from '@/stores/auth'



const menu: Array<Menu | "divider"> = [
  
    {
      icon: "User",
      pageName: "profile-overview-1",
      title: "Profile",
    },
  
  {
    icon: "Home",
    pageName: "dashboard",
    title: "Dashboard",
    subMenu: [
      {
        icon: "Activity",
        pageName: "dashboard-overview-1",
        title: "Dashboard Utama",
      },
    ],
  },

  {
    icon: "User",
    pageName: "Access Control",
    title: "Access Control",
    subMenu: [
      {
        icon: "Shield",
        pageName: "role-overview",
        title: "role",
      },
      {
        icon: "Users",
        pageName: "users",
        title: "user",
      },
    ],
  },

  {
    icon: "CheckCircle",
    pageName: "Verifikasi",
    title: "Verifikasi",
    subMenu: [
      {
        icon: "CheckSquare",
        pageName: "po-verification-list",
        title: "Verifikasi PO",
      },
      {
        icon: "CheckSquare",
        pageName: "penawarans-verifikasi-om",
        title: "Verifikasi Penawaran",
      },
      // {
      //   icon: "CheckSquare",
      //   pageName: "po-verification-detail",
      //   title: "Detail Verifikasi PO",
      // },
     
    ],
  },

  
  

  {
    icon: "Users",
    pageName: "Customer",
    title: "Customer",
    subMenu: [
      {
        icon: "User",
        pageName: "customers-list",
        title: "customer",
      },
      {
        icon: "File",
        pageName: "penawarans-list",
        title: "penawaran",
      },
      // {
      //   icon: 'MapPin',
      //   pageName: 'lcr-list',
      //   title: 'LCR',
      // },
      // {
      //   icon: 'File',
      //   pageName: 'customer-verifications',
      //   title: 'Generate Link',
      // },
      // {
      //   icon: 'File',
      //   pageName: 'review-customer',
      //   title: 'Review Data Customer',
      // },
    
     
     
      
    ],
  },

  // {
  //   icon: "File",
  //   pageName: "po-customers-index",
  //   title: "PO Customer",
  // },

  {
    icon: 'File',
    pageName: 'review-data-customer-admin',
    title: 'Review Data Customer Admin',
  },
  {
    icon: 'File',
    pageName: 'review-data-customer-logistik',
    title: 'Review Data Customer Logistik',
  },
 
  // {
  //   icon: 'File',
  //   pageName: 'verify-data-customer-om',
  //   title: 'Review Data Customer OM',
  // },

  {
    icon: 'CheckCircle',
    pageName: 'sales-confirmations',
    title: 'Sales Confirmation',
  },

 

  {
    icon: "CheckCircle",
    pageName: "verifikasi",
    title: "Verifikasi BM",
    subMenu: [
      {
        icon: "File",
        pageName: "penawarans-verifikasi",
        title: "penawaran",
      },
    ],
  },

  // {
  //   icon: 'File',
  //   pageName: 'verify-data-customer-bm',
  //   title: 'Review Customer BM',
  // },

  // {
  //   icon: 'ClipboardCheck',
  //   pageName: 'sales-confirmations-bm',
  //   title: 'Sales Confirmation (BM)',
  // },

  {
    icon: "CheckCircle",
    pageName: "verifikasi",
    title: "Verifikasi-om",
    subMenu: [
      {
        icon: "File",
        pageName: "penawarans-verifikasi-om",
        title: "penawaran",
      },
    ],
  },

  {
    icon: "Receipt",
    pageName: "Transactions",
    title: "Transactions-Data",
    subMenu: [
      {
        icon: "Inbox",
        pageName: "vendor-pos-list",
        title: "PO Supplier",
      },
      {
        icon: "ClipboardList",
        pageName: "procurement-delivery-requests", // <- sama dgn name router
        title: "Delivery Request",
      },
    ],
  },
  

  {
    icon: "Boxes",
    pageName: "Inventory",
    title: "Inventory-Data",
    subMenu: [
      {
        icon: "Inbox",
        pageName: "StockInventory",
        title: "Stock Inventory",
      },
     

    ],
  },


  {
    icon: "Database",
    pageName: "Master-Data",
    title: "Master-Data",
    subMenu: [

      {
        icon: "Archive",
        pageName: "products",
        title: "Master Produk",
        subMenu: [
          {
            icon: "FileText",
            pageName: "produks-list",
            title: "Produk",
          },
          {
            icon: "FileText",
            pageName: "satuan",
            title: "Satuan",
          },
          {
            icon: "FileText",
            pageName: "ukuran",
            title: "Ukuran",
          },
          {
            icon: "FileText",
            pageName: "jenis-produk-list",
            title: "Jenis",
          },
          
        ],
      },
      {
        icon: "Wallet",
        pageName: "Harga",
        title: "Harga",
        subMenu: [
          {
            icon: "ShoppingBag",
            pageName: "produk-hargas",
            title: "Produks Harga",
          },
          {
            icon: "Tag",
            pageName: "attachment-harga-dasar-list",
            title: "Attachment Harga",
          },
         
        ],
      },
     
      {
        icon: "CreditCard",
        pageName: "vendors-list",
        title: "Vendor",
      },
      {
        icon: "Terminal",
        pageName: "terminals-list",
        title: "Terminal",
      },
      

    ],
  },


  {
    icon: "Database",
    pageName: "Refrensi-Data",
    title: "Refrensi Data",
    subMenu: [


      {
        icon: "Building",
        pageName: "cabang",
        title: "Cabang",
      },
      
      // {
      //   icon: "CreditCard",
      //   pageName: "vendors-list",
      //   title: "Vendor",
      // },
      // {
      //   icon: "Terminal",
      //   pageName: "terminals-list",
      //   title: "Terminal",
      // },
      {
        icon: "Archive",
        pageName: "products",
        title: "Master Produk",
        subMenu: [
          {
            icon: "FileText",
            pageName: "produks-list",
            title: "Produk",
          },
          {
            icon: "FileText",
            pageName: "satuan",
            title: "Satuan",
          },
          {
            icon: "FileText",
            pageName: "ukuran",
            title: "Ukuran",
          },
          {
            icon: "FileText",
            pageName: "jenis-produk-list",
            title: "Jenis",
          },
          
        ],
      },
      
     
      {
        icon: "Wallet",
        pageName: "Harga",
        title: "Harga",
        subMenu: [
          {
            icon: "ShoppingBag",
            pageName: "produk-hargas",
            title: "Produks Harga",
          },
          {
            icon: "Tag",
            pageName: "attachment-harga-dasar-list",
            title: "Attachment Harga",
          },
         
        ],
      },
     
     
    ],
  },

  {
    icon: "MapPin",
    pageName: "Master Wilayah",
    title: "Master Wilayah",
    subMenu: [
      {
        icon: "Flag",
        pageName: "provinsi-list",
        title: "provinsi",
      },
      {
        icon: "Flag",
        pageName: "kabupatens-list",
        title: "kabupaten",
      },
    ],
  },

  {
    icon: "Wallet",
    pageName: "Harga Bm",
    title: "Harga Bm",
    subMenu: [
      {
        icon: "ShoppingBag",
        pageName: "produk-hargas",
        title: "Produks Harga",
      },
      {
        icon: "Tag",
        pageName: "attachment-harga-dasar-list",
        title: "Attachment Harga",
      },
     
    ],
  },


  {
    icon: "Database",
    pageName: "Master Data Logistik",
    title: "Master Logistik",
    subMenu: [
      {
        icon: "Users",
        pageName: "transportir-list",
        title: "transportir",
      },
      {
        icon: "User",
        pageName: "personnel-list",
        title: "personnel",
      },
      {
        icon: "Package",
        pageName: "volumes-list",
        title: "Volume",
      },
      {
        icon: "Pin",
        pageName: "wilayah-angkut-list",
        title: "Wilayah Angkut",
      },
      {
        icon: 'Ship',
        pageName: 'kapals-list',
        title: 'Master Kapal'
      },
      {
        icon: 'Ship',
        pageName: 'ongkos-kapal-list',
        title: 'OA Kapal'
      },
      {
        icon: 'Truck',
        pageName: 'trucks-list',
        title: 'Master Truck',
      },
      {
        icon: 'Truck',
        pageName: 'oa-trucks-list',
        title: 'OA Truck'
      }
      
      
    ],

    
  },

  {
    icon: 'MapPin',
    pageName: 'logistik-lcrs',
    title: 'Verifikasi LCR - Logistik',
  },

      {
        icon: "ClipboardList",
        pageName: "logistics-delivery-plan", // harus sama dengan name route LIST
        title: "Delivery Plan"
      },
    
   
  
  

  
  


  
];

export default menu;
