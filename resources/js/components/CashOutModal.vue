<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm flex flex-col">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Cash Out</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="p-6 space-y-4">
        <p class="text-sm text-gray-500">Record money taken from the drawer (petty cash, expenses, etc.).</p>
        <div>
          <label class="form-label">Amount (LKR) *</label>
          <input v-model.number="amount" type="number" min="0.01" step="0.01" class="form-input" placeholder="0.00" />
        </div>
        <div>
          <label class="form-label">Reason *</label>
          <input v-model="reason" type="text" maxlength="255" class="form-input" placeholder="e.g. Bought ice, Delivery fee…" />
        </div>
        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
      </div>

      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button @click="$emit('close')" class="btn-secondary">Cancel</button>
        <button @click="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Saving…' : 'Record Cash Out' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const emit = defineEmits(['close', 'saved'])

const amount = ref('')
const reason = ref('')
const saving = ref(false)
const error  = ref('')

async function submit() {
  error.value = ''
  if (!amount.value || amount.value <= 0) { error.value = 'Enter a valid amount.'; return }
  if (!reason.value.trim()) { error.value = 'Reason is required.'; return }

  saving.value = true
  try {
    const { data } = await axios.post('/api/cashier-shifts/cash-out', {
      amount: amount.value,
      reason: reason.value.trim(),
    })
    emit('saved', data)
    emit('close')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Something went wrong.'
  } finally {
    saving.value = false
  }
}
</script>
