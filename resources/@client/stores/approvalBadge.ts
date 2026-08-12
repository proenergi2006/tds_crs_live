import { defineStore } from 'pinia'
import axios from 'axios'

export const useApprovalBadgeStore = defineStore('approvalBadge', {
  state: () => ({
    total: 0,
    breakdown: {} as Record<string, number>,
  }),
  actions: {
    async fetch() {
      try {
        const { data } = await axios.get('/api/approvals/pending-count')
        this.total = data.total
        this.breakdown = data.breakdown
      } catch (e) {
        console.error('Gagal memuat approval badge', e)
      }
    },
  },
})
