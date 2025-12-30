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
  <div class="min-h-screen gradient-bg flex items-center justify-center px-4 py-12">
    <div class="max-w-5xl w-full grid md:grid-cols-2 gap-8 items-center">
      <!-- Left Side: Description -->
      <div class="hidden md:block space-y-6 text-center md:text-left">
        <div class="space-y-4">
          <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/20 border border-indigo-500/30 rounded-full text-indigo-300 text-sm font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Limit Order Exchange
          </div>
          <h1 class="text-5xl font-bold bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
            Trading Engine
          </h1>
          <p class="text-xl text-slate-300 font-medium">
            Professional Crypto Spot Trading Platform
          </p>
        </div>

        <div class="space-y-4 pt-6">
          <div class="trading-card p-6">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <h3 class="text-white font-semibold mb-1">Real-Time Order Matching</h3>
                <p class="text-slate-400 text-sm">Place limit buy/sell orders that execute instantly when matched. Full-match execution with FIFO price priority.</p>
              </div>
            </div>
          </div>

          <div class="trading-card p-6">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <h3 class="text-white font-semibold mb-1">Secure Wallet Management</h3>
                <p class="text-slate-400 text-sm">Track your USD balance and crypto assets (BTC, ETH) with real-time updates. Funds are safely locked during order execution.</p>
              </div>
            </div>
          </div>

          <div class="trading-card p-6">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
              <div>
                <h3 class="text-white font-semibold mb-1">Live Orderbook & Analytics</h3>
                <p class="text-slate-400 text-sm">Monitor market depth, view your order history, and track trades with instant WebSocket updates. No page refresh needed.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side: Login Form -->
      <div class="w-full">
        <div class="trading-card p-8 md:p-10">
          <div class="text-center mb-8 md:hidden">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/20 border border-indigo-500/30 rounded-full text-indigo-300 text-sm font-medium mb-4">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              Limit Order Exchange
            </div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent mb-2">
              Trading Engine
            </h1>
            <p class="text-slate-400 text-sm">Professional Crypto Spot Trading Platform</p>
          </div>

          <h2 class="text-2xl font-bold text-white mb-2 hidden md:block">Welcome Back</h2>
          <p class="text-slate-400 mb-8 hidden md:block">Sign in to access your trading dashboard</p>
          
          <form @submit.prevent="handleLogin" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
              <input
                v-model="email"
                type="email"
                required
                class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-500"
                placeholder="you@example.com"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
              <input
                v-model="password"
                type="password"
                required
                class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-500"
                placeholder="••••••••"
              />
            </div>

            <div v-if="error" class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-red-400 text-sm flex items-center gap-2">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ error }}</span>
            </div>

            <button
              type="submit"
              :disabled="isLoading"
              class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold py-3.5 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 flex items-center justify-center gap-2"
            >
              <span v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span>{{ isLoading ? 'Logging in...' : 'Sign In' }}</span>
            </button>
          </form>

          <div class="mt-6 pt-6 border-t border-slate-700">
            <p class="text-xs text-slate-500 text-center">
              Demo credentials available in the project documentation
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
