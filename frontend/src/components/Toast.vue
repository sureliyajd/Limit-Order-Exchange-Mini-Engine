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
  success: 'bg-green-600 border-green-500',
  error: 'bg-red-600 border-red-500',
  info: 'bg-blue-600 border-blue-500',
  warning: 'bg-yellow-600 border-yellow-500',
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
      :class="['fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg border shadow-lg text-white', typeStyles[type]]"
    >
      <span class="text-lg">{{ icons[type] }}</span>
      <span>{{ message }}</span>
      <button @click="visible = false; emit('close')" class="ml-2 opacity-70 hover:opacity-100">✕</button>
    </div>
  </transition>
</template>
