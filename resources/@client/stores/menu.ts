// src/stores/menu.ts
import { defineStore }      from 'pinia'
import { type Icon }        from '@/components/Base/Lucide/Lucide.vue'
import { type Themes }      from '@/stores/theme'
import topMenu              from '@/main/top-menu'
import simpleMenu           from '@/main/simple-menu'
import sideMenu             from '@/main/side-menu'
import { useAuthStore }     from '@/stores/auth'

export interface Menu {
  icon: Icon;
  title: string;
  pageName?: string;
  subMenu?: Menu[];
  ignore?: boolean;
  badge?: {
    counter: 'pendingCfo' | 'pendingCeo'
    role?: number        // kalau diisi, badge hanya tampil untuk role ini
  }
}

export interface MenuState {
  menuValue: Array<Menu | 'divider'>;
}

export const useMenuStore = defineStore('menu', {
  state: (): MenuState => ({
    menuValue: [],
  }),
  getters: {
    menu: (state) => (layout: Themes['layout']) => {
      const auth    = useAuthStore()
      const roleId  = auth.user?.id_role

      if (layout === 'top-menu') {
        return topMenu
      }
      if (layout === 'simple-menu') {
        return simpleMenu
      }
      // hanya untuk side-menu: filter Access Control
      return sideMenu.filter(item => {
        if (item === 'divider') return true
        if (typeof item !== 'string' && item.title === 'Access Control') {
          // hanya return true (tampilkan) jika roleId === 1
          return roleId === 1
        }else if(typeof item !== 'string' && item.title === 'Refrensi Data'){
          return roleId === 2 
        }else if(
          typeof item !== 'string' &&
          (
            item.title === 'Inventory-Data' ||
            item.title === 'Transactions-Data' ||
            item.title === 'Master-Data' ||
            item.title === 'Harga'
          )
        ){
          return roleId === 5
        
        }else if(typeof item !== 'string' && item.title === 'Customer'){
          return roleId === 4
        }else if(typeof item !== 'string' && item.title === 'Master Logistik' || item.title === 'Review Data Customer Logistik' || item.title === 'Delivery Plan'){
          return roleId === 7
        }else if(typeof item !== 'string' && item.title === 'PO Customer'){
          return roleId === 4
        }else if(typeof item !== 'string' && item.title === 'Sales Confirmation' || item.title === 'Review Data Customer Admin'){
          return roleId === 9
        }else if(typeof item !== 'string' && item.title === 'Sales Confirmation (BM)' || item.title === 'Verifikasi BM' || item.title === 'Review Customer BM' || item.title === 'Master Wilayah' ||
          item.title === 'Harga Bm'){
          return roleId === 8
        }else if(typeof item !== 'string' && item.title === 'Verifikasi'){
          return roleId === 2 || roleId === 3
        }else if(typeof item !== 'string' && item.title === 'Verifikasi-om' || item.title === 'Review Data Customer OM'){
          return roleId === 10
        }else if(typeof item !== 'string' && item.title === 'Verifikasi LCR - Logistik'){
          return roleId === 6
        }return true
       
      })
    },
  },
})
