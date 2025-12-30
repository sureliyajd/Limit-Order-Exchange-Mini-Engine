<script setup>
import { ref, computed } from 'vue'
import { tradingStore } from '../stores/tradingStore'

const symbols = ['BTC', 'ETH']
const sides = ['buy', 'sell']
const COMMISSION_RATE = 0.015 // 1.5%

const symbol = ref('BTC')
const side = ref('buy')
const price = ref('')
const amount = ref('')
const isSubmitting = ref(false)
const error = ref('')

// Volume calculation preview
const volumePreview = computed(() => {
  const p = parseFloat(price.value) || 0
  const a = parseFloat(amount.value) || 0
  const volume = p * a
  const commission = volume * COMMISSION_RATE
  const total = side.value === 'buy' ? volume + commission : volume - commission
  return { volume, commission, total }
})

const emit = defineEmits(['orderPlaced'])

async function handleSubmit() {
  error.value = ''
  isSubmitting.value = true
  try {
    const order = await tradingStore.placeOrder({
      symbol: symbol.value,
      side: side.value,
      price: parseFloat(price.value),
      amount: parseFloat(amount.value),
    })
    tradingStore.showToast(`Order placed: ${side.value.toUpperCase()} ${amount.value} ${symbol.value}`, 'success')
    price.value = ''
    amount.value = ''
    emit('orderPlaced')
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to place order'
    tradingStore.showToast(error.value, 'error')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="trading-card p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-bold text-white">Place Order</h2>
        <p class="text-sm text-slate-400 mt-1">Create a limit order</p>
      </div>
      <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
      </div>
    </div>
    
    <form @submit.prevent="handleSubmit" class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-2">Symbol</label>
        <select v-model="symbol" class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
          <option v-for="s in symbols" :key="s" :value="s">{{ s }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-300 mb-2">Order Type</label>
        <div class="flex gap-2">
          <button
            v-for="s in sides"
            :key="s"
            type="button"
            @click="side = s"
            :class="[
              'flex-1 py-3 rounded-lg font-semibold transition-all text-sm',
              side === s
                ? s === 'buy' 
                  ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg shadow-green-500/25' 
                  : 'bg-gradient-to-r from-red-600 to-rose-600 text-white shadow-lg shadow-red-500/25'
                : 'bg-slate-800/50 border border-slate-700 text-slate-400 hover:bg-slate-700/50 hover:text-slate-300'
            ]"
          >
            {{ s.toUpperCase() }}
          </button>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-300 mb-2">Price (USD)</label>
        <input
          v-model="price"
          type="number"
          step="0.00000001"
          min="0"
          required
          class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-500 font-mono"
          placeholder="0.00"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-300 mb-2">Amount</label>
        <input
          v-model="amount"
          type="number"
          step="0.00000001"
          min="0"
          required
          class="w-full bg-slate-800/50 border border-slate-700 text-white rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-500 font-mono"
          placeholder="0.00"
        />
      </div>

      <!-- Volume Preview -->
      <div v-if="volumePreview.volume > 0" class="bg-slate-800/50 border border-slate-700 rounded-lg p-4 space-y-2.5">
        <div class="flex justify-between items-center text-sm">
          <span class="text-slate-400">Volume</span>
          <span class="font-mono text-white font-semibold">${{ volumePreview.volume.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between items-center text-sm">
          <span class="text-slate-400">Commission (1.5%)</span>
          <span class="font-mono text-yellow-400 font-semibold">${{ volumePreview.commission.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between items-center pt-2.5 border-t border-slate-700">
          <span class="text-slate-300 font-medium">{{ side === 'buy' ? 'Total Cost' : 'You Receive' }}</span>
          <span :class="[
            'font-mono font-bold text-lg',
            side === 'buy' ? 'text-red-400' : 'text-green-400'
          ]">
            ${{ volumePreview.total.toFixed(2) }}
          </span>
        </div>
      </div>

      <div v-if="error" class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-red-400 text-sm flex items-center gap-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ error }}</span>
      </div>

      <button
        type="submit"
        :disabled="isSubmitting"
        :class="[
          'w-full py-3.5 rounded-lg font-bold transition-all text-sm shadow-lg',
          side === 'buy'
            ? 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white shadow-green-500/25 hover:shadow-green-500/40'
            : 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white shadow-red-500/25 hover:shadow-red-500/40',
          isSubmitting && 'opacity-50 cursor-not-allowed'
        ]"
      >
        <span v-if="isSubmitting" class="inline-flex items-center gap-2">
          <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>Placing Order...</span>
        </span>
        <span v-else>{{ side.toUpperCase() }} {{ symbol }}</span>
      </button>
    </form>
  </div>
</template>
