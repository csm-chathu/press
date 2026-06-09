import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('token'))
    const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))

    async function login(email, password) {
        const { data } = await axios.post('/login', { email, password })
        token.value = data.token
        user.value  = data.user
        localStorage.setItem('token', data.token)
        localStorage.setItem('user', JSON.stringify(data.user))
        axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
    }

    async function logout() {
        try {
            await axios.post('/api/logout')
        } finally {
            token.value = null
            user.value  = null
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            delete axios.defaults.headers.common['Authorization']
        }
    }

    return { token, user, login, logout }
})
