import { defineStore } from 'pinia'
import { api } from '../lib/api'

export const useAuth = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),
  actions: {
    async login(email, password) {
      const { data } = await api.post('/login', { email, password })
      this.user = data.user
      this.token = data.token
      localStorage.setItem('token', data.token)
    },
    async register(name, email, password) {
      await api.post('/register', { name, email, password })
    },
    async fetchMe() {
      if (!this.token) return
      try {
        const { data } = await api.get('/me')
        this.user = data
      } catch {
        this.logout()
      }
    },
    async logout() {
      try { await api.post('/logout') } catch {}
      this.user = null
      this.token = null
      localStorage.removeItem('token')
    }
  }
})
