<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../api/client'
import { tradingStore } from '../stores/tradingStore'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)

async function handleLogin() {
  error.value = ''
  isLoading.value = true
  
  try {
    const { data } = await apiClient.post('/login', {
      email: email.value,
      password: password.value,
    })
    
    // Store token
    tradingStore.setAuthToken(data.token)
    
    // Fetch profile
    await tradingStore.fetchProfile()
    
    // Subscribe to real-time channel
    if (tradingStore.state.user) {
      tradingStore.subscribeToUserChannel(tradingStore.state.user.id)
    }
    
    // Redirect to dashboard
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Invalid credentials'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-gray-800 rounded-lg p-8">
      <h1 class="text-2xl font-bold text-white text-center mb-8">Trading Engine</h1>
      
      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm text-gray-400 mb-2">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full bg-gray-700 text-white rounded px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label class="block text-sm text-gray-400 mb-2">Password</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full bg-gray-700 text-white rounded px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="••••••••"
          />
        </div>

        <div v-if="error" class="text-red-400 text-sm text-center">{{ error }}</div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ isLoading ? 'Logging in...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>
