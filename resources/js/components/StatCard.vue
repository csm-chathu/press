<template>
  <div class="card text-center">
    <p class="text-sm text-gray-500">{{ label }}</p>
    <div class="mt-2 flex items-center justify-center gap-2">
      <span class="text-2xl">{{ icon }}</span>
      <p class="text-3xl font-bold" :class="valueColor">{{ value }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  label: String,
  value: [String, Number],
  color: { type: String, default: 'gold' },
  icon:  String,
})

const valueColor = computed(() => ({
  gold:   'text-gold-600',
  blue:   'text-blue-600',
  purple: 'text-purple-600',
  red:    'text-red-600',
  green:  'text-green-600',
}[props.color] ?? 'text-gray-800'))
</script>
