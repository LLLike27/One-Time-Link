import { defineStore } from 'pinia'
import { onetimeApi } from '@/api/onetime'
import type { OnetimeLink, CreateLinkParams, Statistics, AccessLog } from '@/api/types'

interface OnetimeState {
  links: OnetimeLink[]
  currentLink: (OnetimeLink & { logs?: AccessLog[] }) | null
  statistics: Statistics | null
  loading: boolean
  total: number
}

export const useOnetimeStore = defineStore('onetime', {
  state: (): OnetimeState => ({
    links: [],
    currentLink: null,
    statistics: null,
    loading: false,
    total: 0,
  }),

  getters: {
    activeLinks: (state) => {
      return state.links.filter((link) => link.status === 1)
    },
    totalVisits: (state) => {
      return state.links.reduce((sum, link) => sum + link.current_visits, 0)
    },
  },

  actions: {
    async createLink(params: CreateLinkParams) {
      this.loading = true
      try {
        const link = await onetimeApi.createLink(params)
        this.links.unshift(link)
        return link
      } finally {
        this.loading = false
      }
    },

    async fetchLinks(params?: any) {
      this.loading = true
      try {
        const data = await onetimeApi.getLinkList(params)
        this.links = data.list
        this.total = data.total
        return data
      } finally {
        this.loading = false
      }
    },

    async fetchLinkDetail(id: number) {
      this.loading = true
      try {
        this.currentLink = await onetimeApi.getLinkDetail(id)
        return this.currentLink
      } finally {
        this.loading = false
      }
    },

    async revokeLink(id: number) {
      await onetimeApi.revokeLink(id)
      const index = this.links.findIndex((link) => link.id === id)
      const listItem = this.links[index]
      if (listItem) {
        listItem.status = 4 // REVOKED
      }
      if (this.currentLink && this.currentLink.id === id) {
        this.currentLink.status = 4
      }
    },

    async extendExpire(id: number, extraSeconds: number) {
      const link = await onetimeApi.extendExpire(id, extraSeconds)
      const index = this.links.findIndex((l) => l.id === id)
      if (index > -1) {
        this.links[index] = link
      }
      if (this.currentLink && this.currentLink.id === id) {
        this.currentLink = { ...this.currentLink, ...link }
      }
      return link
    },

    async fetchStatistics() {
      this.statistics = await onetimeApi.getStatistics()
      return this.statistics
    },

    clearCurrentLink() {
      this.currentLink = null
    },
  },
})
