import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import '../css/app.css'

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
const token = localStorage.getItem('token')
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            router.push('/login')
        }
        return Promise.reject(error)
    }
)

// Keep PHP session alive while the tab is visible (every 4 min)
setInterval(() => {
    if (localStorage.getItem('token') && document.visibilityState === 'visible') {
        axios.get('/api/user').catch(() => {})
    }
}, 4 * 60 * 1000)

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')

