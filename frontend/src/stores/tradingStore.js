import { reactive, computed } from 'vue'
import apiClient from '../api/client'
import echo from '../echo'

const state = reactive({
  user: null,
  balance: '0.00000000',
  assets: [],
  orders: [],
  orderbook: [],
  isAuthenticated: false,
  isLoading: false,
  toast: null, // { message, type }
})

// Toast helper
function showToast(message, type = 'info') {
  state.toast = { message, type, id: Date.now() }
}

function clearToast() {
  state.toast = null
}

// Computed getters
const getAsset = (symbol) => {
  return computed(() => state.assets.find((a) => a.symbol === symbol) || null)
}

const openOrders = computed(() => state.orders.filter((o) => o.status === 1))
const filledOrders = computed(() => state.orders.filter((o) => o.status === 2))
const cancelledOrders = computed(() => state.orders.filter((o) => o.status === 3))

// API Actions
async function fetchProfile() {
  state.isLoading = true
  try {
    const { data } = await apiClient.get('/profile')
    // API returns { data: { id, name, email, balance, assets } }
    state.user = { id: data.data.id, name: data.data.name, email: data.data.email }
    state.balance = data.data.balance
    state.assets = data.data.assets || []
    state.isAuthenticated = true
  } finally {
    state.isLoading = false
  }
}

async function fetchOrders(filters = {}) {
  state.isLoading = true
  try {
    const { data } = await apiClient.get('/orders', { params: filters })
    state.orders = data.data
  } finally {
    state.isLoading = false
  }
}

async function fetchOrderbook(symbol) {
  try {
    const { data } = await apiClient.get('/orderbook', { params: { symbol } })
    state.orderbook = data.data
  } catch (e) {
    state.orderbook = []
  }
}

async function placeOrder(orderData) {
  const { data } = await apiClient.post('/orders', orderData)
  state.orders.unshift(data.data)
  return data.data
}

async function cancelOrder(orderId) {
  const { data } = await apiClient.post(`/orders/${orderId}/cancel`)
  const index = state.orders.findIndex((o) => o.id === orderId)
  if (index !== -1) {
    state.orders[index] = data.data
  }
  return data.data
}

// Echo subscription
let userChannel = null

function subscribeToUserChannel(userId) {
  console.log('[WS] Subscribing to user channel:', userId)
  
  if (userChannel) {
    userChannel.stopListening('OrderMatched')
    echo.leave(`private-user.${userId}`)
  }

  userChannel = echo.private(`user.${userId}`)
  
  userChannel.subscribed(() => {
    console.log('[WS] ✅ Successfully subscribed to private-user.' + userId)
  })
  
  userChannel.error((error) => {
    console.error('[WS] ❌ Subscription error:', error)
  })

  userChannel.listen('OrderMatched', (event) => {
    console.log('[WS] 📨 OrderMatched event received:', event)
    
    if (!state.user) return
    
    // Find the current user's data by user_id
    const userData = [event.buyer, event.seller].find((data) => data?.user_id === state.user.id)
    
    if (!userData) {
      console.log('[WS] No matching data for current user')
      return
    }
    
    console.log('[WS] Updating user data:', userData)

    // Update balance
    if (userData.balance !== undefined) {
      state.balance = userData.balance
    }

    // Update asset
    if (userData.asset) {
      const index = state.assets.findIndex((a) => a.symbol === userData.asset.symbol)
      if (index !== -1) {
        state.assets[index] = { ...state.assets[index], ...userData.asset }
      } else {
        state.assets.push(userData.asset)
      }
    }

    // Update order status
    if (userData.order) {
      const index = state.orders.findIndex((o) => o.id === userData.order.id)
      if (index !== -1) {
        state.orders[index] = { ...state.orders[index], status: 2 } // FILLED status
      }
    }

    // Show toast notification
    const trade = event.trade
    if (trade) {
      showToast(`Order matched! ${trade.amount} ${trade.symbol} @ $${trade.price}`, 'success')
    }
  })
}

function unsubscribeFromUserChannel(userId) {
  if (userChannel) {
    userChannel.stopListening('OrderMatched')
    echo.leave(`private-user.${userId}`)
    userChannel = null
  }
}

// Set auth token
function setAuthToken(token) {
  localStorage.setItem('auth_token', token)
  state.isAuthenticated = true
}

function clearAuth() {
  localStorage.removeItem('auth_token')
  state.user = null
  state.balance = '0.00000000'
  state.assets = []
  state.orders = []
  state.orderbook = []
  state.isAuthenticated = false
}

export const tradingStore = {
  state,
  getAsset,
  openOrders,
  filledOrders,
  cancelledOrders,
  fetchProfile,
  fetchOrders,
  fetchOrderbook,
  placeOrder,
  cancelOrder,
  subscribeToUserChannel,
  unsubscribeFromUserChannel,
  setAuthToken,
  clearAuth,
  showToast,
  clearToast,
}
