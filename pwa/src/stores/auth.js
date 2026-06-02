import { defineStore } from 'pinia'
import api, { setToken } from '../api'

export const useAuth = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: null,
    }),

    getters: {
        isAuthed: (state) => !!state.token,
    },

    actions: {
        init() {
            if (this.token) {
                setToken(this.token)
            }
        },

        async requestOtp(phone) {
            await api.post('/auth/request-otp', { phone })
        },

        async verifyOtp(phone, code) {
            const { data } = await api.post('/auth/verify-otp', { phone, code })
            this.token = data.token
            this.user = data.data
            localStorage.setItem('token', data.token)
            setToken(data.token)
            return data
        },

        // Demo-only: log in without SMS (backend route exists outside production).
        async devLogin(phone) {
            const { data } = await api.post('/dev/login', { phone })
            this.token = data.token
            this.user = data.data
            localStorage.setItem('token', data.token)
            setToken(data.token)
            return data
        },

        logout() {
            this.token = null
            this.user = null
            localStorage.removeItem('token')
            setToken(null)
        },
    },
})
