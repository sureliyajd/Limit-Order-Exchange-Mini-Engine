<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  message: String,
  type: { type: String, default: 'info' }, // success, error, info, warning
  duration: { type: Number, default: 3000 },
})

const emit = defineEmits(['close'])

const visible = ref(true)

const typeStyles = {
  success: 'bg-green-600/90 border-green-500/50 backdrop-blur-sm shadow-lg shadow-green-500/25',
  error: 'bg-red-600/90 border-red-500/50 backdrop-blur-sm shadow-lg shadow-red-500/25',
  info: 'bg-indigo-600/90 border-indigo-500/50 backdrop-blur-sm shadow-lg shadow-indigo-500/25',
  warning: 'bg-yellow-600/90 border-yellow-500/50 backdrop-blur-sm shadow-lg shadow-yellow-500/25',
}

const icons = {
  success: '✓',
  error: '✕',
  info: 'ℹ',
  warning: '⚠',
}

watch(() => props.message, () => {
  visible.value = true
  if (props.duration > 0) {
    setTimeout(() => {
      visible.value = false
      emit('close')
    }, props.duration)
  }
}, { immediate: true })
</script>

<template>
  <transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="translate-x-full opacity-0"
    enter-to-class="translate-x-0 opacity-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="translate-x-0 opacity-100"
    leave-to-class="translate-x-full opacity-0"
  >
    <div
      v-if="visible && message"
      :class="['fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg border text-white font-medium', typeStyles[type]]"
    >
      <span class="text-lg font-bold">{{ icons[type] }}</span>
      <span class="flex-1">{{ message }}</span>
      <button @click="visible = false; emit('close')" class="ml-2 opacity-70 hover:opacity-100 transition-opacity w-5 h-5 flex items-center justify-center rounded hover:bg-white/10">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </transition>
</template>
