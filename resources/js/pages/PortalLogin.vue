<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-sm">

      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-amber-500 rounded-2xl shadow-lg mb-4">
          <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800">Client Portal</h1>
        <p class="text-sm text-gray-500 mt-1">LMUC Press — Track your orders & jobs</p>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email</label>
            <input v-model="form.email" type="email" required autocomplete="email"
              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent outline-none"
              placeholder="you@company.com" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password</label>
            <input v-model="form.password" type="password" required autocomplete="current-password"
              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent outline-none"
              placeholder="••••••••" />
          </div>

          <div v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-100 px-3 py-2.5 rounded-xl">
            {{ error }}
          </div>

          <button type="submit" :disabled="loading"
            class="w-full bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white font-semibold py-2.5 rounded-xl text-sm flex items-center justify-center gap-2">
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            {{ loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-gray-400 mt-4">
        Staff? <router-link to="/login" class="text-amber-600 hover:underline">Sign in here</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()
const form   = reactive({ email: '', password: '' })
const error  = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    if (auth.user?.role !== 'client') {
      await auth.logout()
      error.value = 'This account does not have client portal access.'
      return
    }
    router.push('/portal')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Invalid email or password'
  } finally {
    loading.value = false
  }
}
</script>
