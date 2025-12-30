<script setup>
import { ref, onMounted, watch } from 'vue'
import apiClient from '../api/client'

const props = defineProps({
  visible: Boolean,
})

const emit = defineEmits(['close'])

const summary = ref(null)
const trades = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const isLoading = ref(false)
const currentPage = ref(1)

async function fetchSummary() {
  try {
    const { data } = await apiClient.get('/trades/summary')
    summary.value = data.data
  } catch (e) {
    console.error('Failed to fetch summary', e)
  }
}

async function fetchTrades(page = 1) {
  isLoading.value = true
  try {
    const { data } = await apiClient.get('/trades', { params: { page, per_page: 10 } })
    trades.value = data.data
    meta.value = data.meta
    currentPage.value = page
  } catch (e) {
    console.error('Failed to fetch trades', e)
  } finally {
    isLoading.value = false
  }
}

watch(() => props.visible, (val) => {
  if (val) {
    fetchSummary()
    fetchTrades(1)
  }
})

onMounted(() => {
  if (props.visible) {
    fetchSummary()
    fetchTrades(1)
  }
})
</script>

<template>
  <teleport to="body">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="emit('close')"></div>
      
      <!-- Modal -->
      <div class="relative trading-card w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-slate-700 bg-gradient-to-r from-indigo-500/10 to-purple-500/10">
          <div>
            <h2 class="text-2xl font-bold text-white">Trades & Commissions</h2>
            <p class="text-sm text-slate-400 mt-1">Platform-wide trading analytics</p>
          </div>
          <button @click="emit('close')" class="w-8 h-8 rounded-lg bg-slate-800/50 hover:bg-slate-700/50 text-slate-400 hover:text-white transition-all flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          <!-- Admin Notice -->
          <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-yellow-400 text-sm">
              <span class="font-semibold">Super Admin View:</span> 
              This section displays all platform trades and commissions. Role-based access control is not yet implemented.
            </p>
          </div>

          <!-- Summary Cards -->
          <div v-if="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="trading-card p-5 text-center">
              <div class="w-12 h-12 rounded-lg bg-indigo-500/20 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <p class="text-slate-400 text-sm mb-2">Total Trades</p>
              <p class="text-3xl font-bold text-white font-mono">{{ summary.total_trades }}</p>
            </div>
            <div class="trading-card p-5 text-center">
              <div class="w-12 h-12 rounded-lg bg-blue-500/20 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
              <p class="text-slate-400 text-sm mb-2">Total Volume</p>
              <p class="text-3xl font-bold text-blue-400 font-mono">${{ summary.total_volume }}</p>
            </div>
            <div class="trading-card p-5 text-center">
              <div class="w-12 h-12 rounded-lg bg-green-500/20 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <p class="text-slate-400 text-sm mb-2">Total Commission</p>
              <p class="text-3xl font-bold text-green-400 font-mono">${{ summary.total_commission }}</p>
              <p class="text-xs text-slate-500 mt-1">({{ summary.commission_rate }})</p>
            </div>
          </div>

          <!-- Trades Table -->
          <div v-if="isLoading" class="text-center py-12 text-slate-400">
            <div class="w-8 h-8 border-2 border-slate-600 border-t-indigo-500 rounded-full animate-spin mx-auto mb-2"></div>
            <p>Loading trades...</p>
          </div>
          <div v-else-if="trades.length === 0" class="text-center py-12 text-slate-500">
            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm">No trades yet</p>
          </div>
          <div v-else>
            <div class="overflow-x-auto">
              <table class="w-full text-sm font-mono">
                <thead>
                  <tr class="text-slate-400 border-b border-slate-700">
                    <th class="text-left py-3 px-2 font-semibold text-xs uppercase tracking-wider">ID</th>
                    <th class="text-left py-3 px-2 font-semibold text-xs uppercase tracking-wider">Symbol</th>
                    <th class="text-right py-3 px-2 font-semibold text-xs uppercase tracking-wider">Price</th>
                    <th class="text-right py-3 px-2 font-semibold text-xs uppercase tracking-wider">Amount</th>
                    <th class="text-right py-3 px-2 font-semibold text-xs uppercase tracking-wider">Volume</th>
                    <th class="text-right py-3 px-2 font-semibold text-xs uppercase tracking-wider">Commission</th>
                    <th class="text-left py-3 px-2 font-semibold text-xs uppercase tracking-wider">Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="trade in trades" :key="trade.id" class="border-b border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                    <td class="py-3 px-2 text-slate-400">#{{ trade.id }}</td>
                    <td class="py-3 px-2">
                      <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-slate-800/50 font-semibold text-white">
                        {{ trade.symbol }}
                      </span>
                    </td>
                    <td class="py-3 px-2 text-right text-white font-semibold">${{ parseFloat(trade.price).toFixed(2) }}</td>
                    <td class="py-3 px-2 text-right text-white font-semibold">{{ parseFloat(trade.amount).toFixed(8) }}</td>
                    <td class="py-3 px-2 text-right text-blue-400 font-semibold">${{ parseFloat(trade.usd_volume).toFixed(2) }}</td>
                    <td class="py-3 px-2 text-right text-green-400 font-semibold">${{ parseFloat(trade.commission).toFixed(2) }}</td>
                    <td class="py-3 px-2 text-slate-400 text-xs font-sans">{{ new Date(trade.created_at).toLocaleString() }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="flex justify-center items-center gap-3 mt-6 pt-6 border-t border-slate-700">
              <button
                @click="fetchTrades(currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-700/50 transition-all text-sm font-medium text-slate-300"
              >
                Previous
              </button>
              <span class="text-slate-400 text-sm font-medium">
                Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)
              </span>
              <button
                @click="fetchTrades(currentPage + 1)"
                :disabled="currentPage === meta.last_page"
                class="px-4 py-2 bg-slate-800/50 border border-slate-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-700/50 transition-all text-sm font-medium text-slate-300"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>
