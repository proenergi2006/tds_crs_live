import { defineStore } from 'pinia'
import axios from 'axios'

export const useApprovalBadgeStore = defineStore('approvalBadge', {
  state: () => ({
    vendorPo: 0,
    penawaran: 0,
  }),
  getters: {
    total: (s): number => s.vendorPo + s.penawaran,
  },
  actions: {
    async fetch() {
      try {
        const { data } = await axios.get('/api/approvals/pending-count')
        this.vendorPo = data.breakdown.vendor_po
        this.penawaran = data.breakdown.penawaran
      } catch { /* non-CEO users get 403 — silently ignored */ }
    },
  },
})
