// src/stores/poCounters.ts
import { defineStore } from 'pinia'
import axios from 'axios'

export const usePoCounters = defineStore('poCounters', {
  state: () => ({
    pendingCfo: 0,
    pendingCeo: 0,
    loading: false,
  }),
  actions: {
    async refresh() {
      this.loading = true
      try {
        const [{ data: cfo }, { data: ceo }] = await Promise.all([
          axios.get('/api/po-verification', { params: { per_page: 1, disposisi_po: 1 } }),
          axios.get('/api/po-verification', { params: { per_page: 1, disposisi_po: 2 } }),
        ])
        this.pendingCfo = Number(cfo?.total ?? 0)
        this.pendingCeo = Number(ceo?.total ?? 0)
      } finally {
        this.loading = false
      }
    },
  },
})
