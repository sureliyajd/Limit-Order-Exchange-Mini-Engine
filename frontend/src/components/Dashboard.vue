<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { tradingStore } from '../stores/tradingStore'
import apiClient from '../api/client'
import OrderForm from './OrderForm.vue'
import Toast from './Toast.vue'
import TradesModal from './TradesModal.vue'

const router = useRouter()
const { state, openOrders, filledOrders, cancelledOrders, fetchProfile, fetchOrders, fetchOrderbook, cancelOrder, subscribeToUserChannel, unsubscribeFromUserChannel, clearAuth, showToast, clearToast } = tradingStore

const selectedSymbol = ref('BTC')
const cancellingId = ref(null)
const showTradesModal = ref(false)

// Order filters
const filterSymbol = ref('')
const filterSide = ref('')
const filterStatus = ref('')

const statusLabels = { 1: 'Open', 2: 'Filled', 3: 'Cancelled' }
const statusColors = { 1: 'text-yellow-400', 2: 'text-green-400', 3: 'text-gray-400' }

async function handleCancel(orderId) {
  cancellingId.value = orderId
  try {
    await cancelOrder(orderId)
    showToast('Order cancelled', 'success')
  } catch (e) {
    showToast('Failed to cancel order', 'error')
  } finally {
    cancellingId.value = null
  }
}

function loadOrders() {
  const filters = {}
  if (filterSymbol.value) filters.symbol = filterSymbol.value
  if (filterSide.value) filters.side = filterSide.value
  if (filterStatus.value) filters.status = filterStatus.value
  fetchOrders(filters)
}

function loadOrderbook() {
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
  loadOrderbook()
})

onUnmounted(() => {
  if (state.user) {
    unsubscribeFromUserChannel(state.user.id)
  }
})
</script>

<template>
  <!-- Toast -->
  <Toast
    v-if="state.toast"
    :key="state.toast.id"
    :message="state.toast.message"
    :type="state.toast.type"
    @close="clearToast"
  />

  <div class="min-h-screen gradient-bg text-white p-4 md:p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 md:mb-8">
        <div>
          <h1 class="text-3xl md:text-4xl font-bold tracking-tight bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">
            Trading Dashboard
          </h1>
          <p class="text-slate-400 text-sm mt-1">Real-time order matching & portfolio management</p>
        </div>
        <div class="flex items-center gap-4">
          <div v-if="state.user" class="text-right hidden sm:block">
            <p class="text-white font-semibold">{{ state.user.name }}</p>
            <p class="text-sm text-slate-400">{{ state.user.email }}</p>
          </div>
          <button
            @click="handleLogout"
            class="px-4 py-2 bg-slate-800/50 border border-slate-700 hover:bg-slate-700/50 text-slate-300 rounded-lg transition-all font-medium text-sm"
          >
            Logout
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Left Column: Wallet + Order Form -->
        <div class="space-y-4 md:space-y-6">
          <!-- USD Balance -->
          <div class="trading-card p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
            <div class="relative">
              <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">USD Balance</h2>
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <p class="text-4xl font-bold text-green-400 font-mono glow-green">${{ state.balance }}</p>
              <p class="text-xs text-slate-500 mt-2">Available for trading</p>
            </div>
          </div>

          <!-- Asset Balances -->
          <div class="trading-card p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-white">Portfolio Assets</h2>
              <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
              </div>
            </div>
            <div v-if="state.assets.length === 0" class="text-center py-8 text-slate-500">
              <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              <p class="text-sm">No assets yet</p>
            </div>
            <div v-else class="space-y-3">
              <div v-for="asset in state.assets" :key="asset.symbol" class="flex justify-between items-center p-3 bg-slate-800/30 rounded-lg border border-slate-700/50 hover:border-slate-600 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center font-bold text-indigo-300">
                    {{ asset.symbol }}
                  </div>
                  <div>
                    <p class="font-semibold text-white">{{ asset.symbol }}</p>
                    <p v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-yellow-400">
                      Locked: {{ asset.locked_amount }}
                    </p>
                  </div>
                </div>
                <div class="text-right font-mono">
                  <p class="text-lg font-bold text-white">{{ asset.amount }}</p>
                  <p v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-slate-500">
                    Available: {{ (parseFloat(asset.amount) - parseFloat(asset.locked_amount)).toFixed(8) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Form -->
          <OrderForm @orderPlaced="loadOrders(); loadOrderbook()" />
        </div>

        <!-- Right Column: Orderbook + Orders -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
          <!-- Symbol Selector for Orderbook -->
          <div class="flex gap-2">
            <button
              v-for="sym in ['BTC', 'ETH']"
              :key="sym"
              @click="selectedSymbol = sym; loadOrderbook()"
              :class="[
                'px-5 py-2.5 rounded-lg font-semibold transition-all text-sm',
                selectedSymbol === sym
                  ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/25'
                  : 'bg-slate-800/50 border border-slate-700 text-slate-400 hover:bg-slate-700/50 hover:text-slate-300'
              ]"
            >
              {{ sym }}
            </button>
          </div>

          <!-- Orderbook -->
          <div class="trading-card p-6">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h2 class="text-xl font-bold text-white">Orderbook</h2>
                <p class="text-sm text-slate-400 mt-1">{{ selectedSymbol }} Market Depth</p>
              </div>
              <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <!-- Buy Orders -->
              <div>
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-700">
                  <div class="w-2 h-2 rounded-full bg-green-400 glow-green"></div>
                  <h3 class="text-green-400 font-semibold">Bids (Buy)</h3>
                </div>
                <div class="space-y-0.5 text-sm font-mono max-h-64 overflow-y-auto">
                  <div class="flex justify-between text-slate-500 border-b border-slate-800 pb-2 mb-2 font-sans text-xs uppercase tracking-wider">
                    <span>Price (USD)</span>
                    <span>Amount</span>
                  </div>
                  <div
                    v-for="order in state.orderbook.filter(o => o.side === 'buy' && o.symbol === selectedSymbol).slice().reverse()"
                    :key="order.id"
                    class="flex justify-between text-green-400 hover:bg-green-500/10 px-2 py-1 rounded transition-colors cursor-pointer"
                  >
                    <span class="font-semibold">{{ parseFloat(order.price).toFixed(2) }}</span>
                    <span>{{ parseFloat(order.amount).toFixed(8) }}</span>
                  </div>
                  <div v-if="state.orderbook.filter(o => o.side === 'buy' && o.symbol === selectedSymbol).length === 0" class="text-center py-8 text-slate-500 text-xs">
                    No buy orders
                  </div>
                </div>
              </div>

              <!-- Sell Orders -->
              <div>
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-700">
                  <div class="w-2 h-2 rounded-full bg-red-400 glow-red"></div>
                  <h3 class="text-red-400 font-semibold">Asks (Sell)</h3>
                </div>
                <div class="space-y-0.5 text-sm font-mono max-h-64 overflow-y-auto">
                  <div class="flex justify-between text-slate-500 border-b border-slate-800 pb-2 mb-2 font-sans text-xs uppercase tracking-wider">
                    <span>Price (USD)</span>
                    <span>Amount</span>
                  </div>
                  <div
                    v-for="order in state.orderbook.filter(o => o.side === 'sell' && o.symbol === selectedSymbol)"
                    :key="order.id"
                    class="flex justify-between text-red-400 hover:bg-red-500/10 px-2 py-1 rounded transition-colors cursor-pointer"
                  >
                    <span class="font-semibold">{{ parseFloat(order.price).toFixed(2) }}</span>
                    <span>{{ parseFloat(order.amount).toFixed(8) }}</span>
                  </div>
                  <div v-if="state.orderbook.filter(o => o.side === 'sell' && o.symbol === selectedSymbol).length === 0" class="text-center py-8 text-slate-500 text-xs">
                    No sell orders
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Orders List -->
          <div class="trading-card p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
              <div>
                <h2 class="text-xl font-bold text-white">Order History</h2>
                <p class="text-sm text-slate-400 mt-1">Your trading activity</p>
              </div>
              <button
                @click="showTradesModal = true"
                class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm rounded-lg transition-all font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40"
              >
                Trades & Commissions
              </button>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-2 mb-4">
              <select
                v-model="filterSymbol"
                @change="loadOrders"
                class="bg-slate-800/50 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
              >
                <option value="">All Symbols</option>
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
              </select>
              <select
                v-model="filterSide"
                @change="loadOrders"
                class="bg-slate-800/50 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
              >
                <option value="">All Sides</option>
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
              </select>
              <select
                v-model="filterStatus"
                @change="loadOrders"
                class="bg-slate-800/50 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
              >
                <option value="">All Status</option>
                <option value="1">Open</option>
                <option value="2">Filled</option>
                <option value="3">Cancelled</option>
              </select>
              <button
                v-if="filterSymbol || filterSide || filterStatus"
                @click="filterSymbol = ''; filterSide = ''; filterStatus = ''; loadOrders()"
                class="text-sm text-slate-400 hover:text-white px-3 py-2 rounded-lg hover:bg-slate-800/50 transition-colors"
              >
                Clear filters
              </button>
            </div>
            
            <div v-if="state.isLoading" class="text-center py-12 text-slate-400">
              <div class="w-8 h-8 border-2 border-slate-600 border-t-indigo-500 rounded-full animate-spin mx-auto mb-2"></div>
              <p>Loading orders...</p>
            </div>
            <div v-else-if="state.orders.length === 0" class="text-center py-12 text-slate-500">
              <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-sm">No orders found</p>
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm font-mono">
                <thead>
                  <tr class="text-slate-400 border-b border-slate-700">
                    <th class="text-left py-3 px-2 font-semibold text-xs uppercase tracking-wider">Symbol</th>
                    <th class="text-left py-3 px-2 font-semibold text-xs uppercase tracking-wider">Side</th>
                    <th class="text-right py-3 px-2 font-semibold text-xs uppercase tracking-wider">Price</th>
                    <th class="text-right py-3 px-2 font-semibold text-xs uppercase tracking-wider">Amount</th>
                    <th class="text-center py-3 px-2 font-semibold text-xs uppercase tracking-wider">Status</th>
                    <th class="text-right py-3 px-2 font-semibold text-xs uppercase tracking-wider">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in state.orders" :key="order.id" class="border-b border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                    <td class="py-3 px-2">
                      <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-slate-800/50 font-semibold text-white">
                        {{ order.symbol }}
                      </span>
                    </td>
                    <td class="py-3 px-2">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold',
                        order.side === 'buy' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'
                      ]">
                        {{ order.side.toUpperCase() }}
                      </span>
                    </td>
                    <td class="py-3 px-2 text-right font-semibold text-white">${{ parseFloat(order.price).toFixed(2) }}</td>
                    <td class="py-3 px-2 text-right font-semibold text-white">{{ parseFloat(order.amount).toFixed(8) }}</td>
                    <td class="py-3 px-2 text-center">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold',
                        statusColors[order.status],
                        order.status === 1 ? 'bg-yellow-500/20' : order.status === 2 ? 'bg-green-500/20' : 'bg-slate-700/50'
                      ]">
                        {{ statusLabels[order.status] }}
                      </span>
                    </td>
                    <td class="py-3 px-2 text-right">
                      <button
                        v-if="order.status === 1"
                        @click="handleCancel(order.id)"
                        :disabled="cancellingId === order.id"
                        class="px-3 py-1.5 bg-red-600/80 hover:bg-red-600 rounded-lg text-xs font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition-all"
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

  <!-- Trades Modal -->
  <TradesModal :visible="showTradesModal" @close="showTradesModal = false" />
</template>
