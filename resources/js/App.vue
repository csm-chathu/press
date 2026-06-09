<template>
  <router-view />
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import axios from 'axios'

let pingInterval = null

onMounted(() => {
  pingInterval = setInterval(() => {
    axios.get('/api/health').catch(() => {})
  }, 3 * 60 * 1000) // every 3 minutes
})

onUnmounted(() => {
  clearInterval(pingInterval)
})
</script>
