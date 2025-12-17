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
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/70" @click="emit('close')"></div>
      
      <!-- Modal -->
      <div class="relative bg-gray-800 rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden mx-4">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-700">
          <h2 class="text-xl font-bold text-white">Trades & Commissions</h2>
          <button @click="emit('close')" class="text-gray-400 hover:text-white text-2xl">&times;</button>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
          <!-- Admin Notice -->
          <div class="bg-yellow-900/30 border border-yellow-600/50 rounded-lg p-4 mb-6">
            <p class="text-yellow-400 text-sm">
              <span class="font-semibold">⚠️ Super Admin View:</span> 
              This section displays all platform trades and commissions. Role-based access control is not yet implemented.
            </p>
          </div>

          <!-- Summary Cards -->
          <div v-if="summary" class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-700/50 rounded-lg p-4 text-center">
              <p class="text-gray-400 text-sm">Total Trades</p>
              <p class="text-2xl font-bold text-white font-mono">{{ summary.total_trades }}</p>
            </div>
            <div class="bg-gray-700/50 rounded-lg p-4 text-center">
              <p class="text-gray-400 text-sm">Total Volume</p>
              <p class="text-2xl font-bold text-blue-400 font-mono">${{ summary.total_volume }}</p>
            </div>
            <div class="bg-gray-700/50 rounded-lg p-4 text-center">
              <p class="text-gray-400 text-sm">Total Commission ({{ summary.commission_rate }})</p>
              <p class="text-2xl font-bold text-green-400 font-mono">${{ summary.total_commission }}</p>
            </div>
          </div>

          <!-- Trades Table -->
          <div v-if="isLoading" class="text-gray-400 text-center py-8">Loading...</div>
          <div v-else-if="trades.length === 0" class="text-gray-500 text-center py-8">No trades yet</div>
          <div v-else>
            <table class="w-full text-sm">
              <thead>
                <tr class="text-gray-400 border-b border-gray-700">
                  <th class="text-left py-2">ID</th>
                  <th class="text-left py-2">Symbol</th>
                  <th class="text-right py-2">Price</th>
                  <th class="text-right py-2">Amount</th>
                  <th class="text-right py-2">Volume</th>
                  <th class="text-right py-2">Commission</th>
                  <th class="text-left py-2">Date</th>
                </tr>
              </thead>
              <tbody class="font-mono">
                <tr v-for="trade in trades" :key="trade.id" class="border-b border-gray-700/50">
                  <td class="py-3 text-gray-400">#{{ trade.id }}</td>
                  <td class="py-3 text-white">{{ trade.symbol }}</td>
                  <td class="py-3 text-right text-white">${{ trade.price }}</td>
                  <td class="py-3 text-right text-white">{{ trade.amount }}</td>
                  <td class="py-3 text-right text-blue-400">${{ trade.usd_volume }}</td>
                  <td class="py-3 text-right text-green-400 pr-4">${{ trade.commission }}</td>
                  <td class="py-3 text-gray-400 text-xs pl-4">{{ new Date(trade.created_at).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="meta.last_page > 1" class="flex justify-center items-center gap-2 mt-4 pt-4 border-t border-gray-700">
              <button
                @click="fetchTrades(currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-3 py-1 bg-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-600"
              >
                Previous
              </button>
              <span class="text-gray-400 text-sm">
                Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)
              </span>
              <button
                @click="fetchTrades(currentPage + 1)"
                :disabled="currentPage === meta.last_page"
                class="px-3 py-1 bg-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-600"
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
