<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="$emit('cancel')"
      >
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6">
          <h3 class="font-semibold text-gray-900 mb-1">{{ title }}</h3>
          <p class="text-sm text-gray-500 mb-5">{{ message }}</p>
          <div class="flex gap-3 justify-end">
            <button @click="$emit('cancel')" class="btn-secondary px-4 py-1.5 text-sm">Cancel</button>
            <button @click="$emit('confirm')" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-md text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition-colors">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
              </svg>
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  show:    { type: Boolean, required: true },
  title:   { type: String,  default: 'Confirm Delete' },
  message: { type: String,  default: 'This action cannot be undone.' },
})
defineEmits(['confirm', 'cancel'])
</script>

<style scoped>
.confirm-fade-enter-active, .confirm-fade-leave-active { transition: opacity 0.15s ease; }
.confirm-fade-enter-from, .confirm-fade-leave-to       { opacity: 0; }
</style>
