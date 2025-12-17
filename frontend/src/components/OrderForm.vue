<script setup>
import { ref } from 'vue'
import { tradingStore } from '../stores/tradingStore'

const symbols = ['BTC', 'ETH']
const sides = ['buy', 'sell']

const symbol = ref('BTC')
const side = ref('buy')
const price = ref('')
const amount = ref('')
const isSubmitting = ref(false)
const error = ref('')

async function handleSubmit() {
  error.value = ''
  isSubmitting.value = true
  try {
    await tradingStore.placeOrder({
      symbol: symbol.value,
      side: side.value,
      price: parseFloat(price.value),
      amount: parseFloat(amount.value),
    })
    price.value = ''
    amount.value = ''
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to place order'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="bg-gray-800 rounded-lg p-6">
    <h2 class="text-xl font-semibold text-white mb-4">Place Order</h2>
    
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm text-gray-400 mb-1">Symbol</label>
        <select v-model="symbol" class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
          <option v-for="s in symbols" :key="s" :value="s">{{ s }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm text-gray-400 mb-1">Side</label>
        <div class="flex gap-2">
          <button
            v-for="s in sides"
            :key="s"
            type="button"
            @click="side = s"
            :class="[
              'flex-1 py-2 rounded font-medium transition-colors',
              side === s
                ? s === 'buy' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
                : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
            ]"
          >
            {{ s.toUpperCase() }}
          </button>
        </div>
      </div>

      <div>
        <label class="block text-sm text-gray-400 mb-1">Price (USD)</label>
        <input
          v-model="price"
          type="number"
          step="0.00000001"
          min="0"
          required
          class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
          placeholder="0.00"
        />
      </div>

      <div>
        <label class="block text-sm text-gray-400 mb-1">Amount</label>
        <input
          v-model="amount"
          type="number"
          step="0.00000001"
          min="0"
          required
          class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
          placeholder="0.00"
        />
      </div>

      <div v-if="error" class="text-red-400 text-sm">{{ error }}</div>

      <button
        type="submit"
        :disabled="isSubmitting"
        :class="[
          'w-full py-3 rounded font-semibold transition-colors',
          side === 'buy'
            ? 'bg-green-600 hover:bg-green-700 text-white'
            : 'bg-red-600 hover:bg-red-700 text-white',
          isSubmitting && 'opacity-50 cursor-not-allowed'
        ]"
      >
        {{ isSubmitting ? 'Placing...' : `${side.toUpperCase()} ${symbol}` }}
      </button>
    </form>
  </div>
</template>
