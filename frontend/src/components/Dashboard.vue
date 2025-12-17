<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { tradingStore } from '../stores/tradingStore'
import apiClient from '../api/client'
import OrderForm from './OrderForm.vue'

const router = useRouter()
const { state, openOrders, filledOrders, cancelledOrders, fetchProfile, fetchOrders, fetchOrderbook, cancelOrder, subscribeToUserChannel, unsubscribeFromUserChannel, clearAuth } = tradingStore

const selectedSymbol = ref('BTC')
const cancellingId = ref(null)

const statusLabels = { 1: 'Open', 2: 'Filled', 3: 'Cancelled' }
const statusColors = { 1: 'text-yellow-400', 2: 'text-green-400', 3: 'text-gray-400' }

async function handleCancel(orderId) {
  cancellingId.value = orderId
  try {
    await cancelOrder(orderId)
  } finally {
    cancellingId.value = null
  }
}

function loadOrders() {
  fetchOrders(selectedSymbol.value)
  fetchOrderbook(selectedSymbol.value)
}

async function handleLogout() {
  try {
    await apiClient.post('/logout')
  } catch (e) {
    // Ignore errors
  }
  if (state.user) {
    unsubscribeFromUserChannel(state.user.id)
  }
  clearAuth()
  router.push('/login')
}

onMounted(async () => {
  console.log('[Dashboard] onMounted - fetching profile')
  await fetchProfile()
  console.log('[Dashboard] Profile fetched, state.user:', state.user)
  if (state.user) {
    console.log('[Dashboard] Calling subscribeToUserChannel with id:', state.user.id)
    subscribeToUserChannel(state.user.id)
  } else {
    console.log('[Dashboard] ❌ state.user is null/undefined, cannot subscribe')
  }
  loadOrders()
})

onUnmounted(() => {
  if (state.user) {
    unsubscribeFromUserChannel(state.user.id)
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white p-6">
    <div class="max-w-6xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Trading Dashboard</h1>
        <button
          @click="handleLogout"
          class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded transition-colors"
        >
          Logout
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Wallet + Order Form -->
        <div class="space-y-6">
          <!-- USD Balance -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-400 mb-2">USD Balance</h2>
            <p class="text-3xl font-bold text-green-400">${{ state.balance }}</p>
          </div>

          <!-- Asset Balances -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-400 mb-4">Assets</h2>
            <div v-if="state.assets.length === 0" class="text-gray-500">No assets</div>
            <div v-else class="space-y-3">
              <div v-for="asset in state.assets" :key="asset.symbol" class="flex justify-between items-center">
                <span class="font-medium">{{ asset.symbol }}</span>
                <div class="text-right">
                  <p class="text-white">{{ asset.amount }}</p>
                  <p v-if="parseFloat(asset.locked_amount) > 0" class="text-sm text-yellow-400">
                    Locked: {{ asset.locked_amount }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Form -->
          <OrderForm />
        </div>

        <!-- Right Column: Orderbook + Orders -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Symbol Selector -->
          <div class="flex gap-2">
            <button
              v-for="sym in ['BTC', 'ETH']"
              :key="sym"
              @click="selectedSymbol = sym; loadOrders()"
              :class="[
                'px-4 py-2 rounded font-medium transition-colors',
                selectedSymbol === sym
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
              ]"
            >
              {{ sym }}
            </button>
          </div>

          <!-- Orderbook -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Orderbook - {{ selectedSymbol }}</h2>
            
            <div class="grid grid-cols-2 gap-4">
              <!-- Buy Orders -->
              <div>
                <h3 class="text-green-400 font-medium mb-2">Buy Orders</h3>
                <div class="space-y-1 text-sm">
                  <div class="flex justify-between text-gray-500 border-b border-gray-700 pb-1">
                    <span>Price</span>
                    <span>Amount</span>
                  </div>
                  <div
                    v-for="order in state.orderbook.filter(o => o.side === 'buy' && o.symbol === selectedSymbol)"
                    :key="order.id"
                    class="flex justify-between text-green-400"
                  >
                    <span>{{ order.price }}</span>
                    <span>{{ order.amount }}</span>
                  </div>
                  <div v-if="state.orderbook.filter(o => o.side === 'buy' && o.symbol === selectedSymbol).length === 0" class="text-gray-500">
                    No buy orders
                  </div>
                </div>
              </div>

              <!-- Sell Orders -->
              <div>
                <h3 class="text-red-400 font-medium mb-2">Sell Orders</h3>
                <div class="space-y-1 text-sm">
                  <div class="flex justify-between text-gray-500 border-b border-gray-700 pb-1">
                    <span>Price</span>
                    <span>Amount</span>
                  </div>
                  <div
                    v-for="order in state.orderbook.filter(o => o.side === 'sell' && o.symbol === selectedSymbol)"
                    :key="order.id"
                    class="flex justify-between text-red-400"
                  >
                    <span>{{ order.price }}</span>
                    <span>{{ order.amount }}</span>
                  </div>
                  <div v-if="state.orderbook.filter(o => o.side === 'sell' && o.symbol === selectedSymbol).length === 0" class="text-gray-500">
                    No sell orders
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Orders List -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">My Orders</h2>
            
            <div v-if="state.isLoading" class="text-gray-400">Loading...</div>
            <div v-else-if="state.orders.length === 0" class="text-gray-500">No orders</div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-gray-400 border-b border-gray-700">
                    <th class="text-left py-2">Symbol</th>
                    <th class="text-left py-2">Side</th>
                    <th class="text-right py-2">Price</th>
                    <th class="text-right py-2">Amount</th>
                    <th class="text-center py-2">Status</th>
                    <th class="text-right py-2">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in state.orders" :key="order.id" class="border-b border-gray-700/50">
                    <td class="py-3">{{ order.symbol }}</td>
                    <td :class="order.side === 'buy' ? 'text-green-400' : 'text-red-400'">
                      {{ order.side.toUpperCase() }}
                    </td>
                    <td class="text-right">{{ order.price }}</td>
                    <td class="text-right">{{ order.amount }}</td>
                    <td :class="['text-center', statusColors[order.status]]">
                      {{ statusLabels[order.status] }}
                    </td>
                    <td class="text-right">
                      <button
                        v-if="order.status === 1"
                        @click="handleCancel(order.id)"
                        :disabled="cancellingId === order.id"
                        class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded text-xs font-medium disabled:opacity-50"
                      >
                        {{ cancellingId === order.id ? '...' : 'Cancel' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
