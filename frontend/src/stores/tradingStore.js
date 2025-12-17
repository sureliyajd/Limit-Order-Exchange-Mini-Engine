import { reactive, computed } from 'vue'
import apiClient from '../api/client'
import echo from '../echo'

const state = reactive({
  user: null,
  balance: '0.00000000',
  assets: [],
  orders: [],
  isAuthenticated: false,
  isLoading: false,
})

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
    state.user = data.data.user
    state.balance = data.data.balance
    state.assets = data.data.assets
    state.isAuthenticated = true
  } finally {
    state.isLoading = false
  }
}

async function fetchOrders(symbol) {
  state.isLoading = true
  try {
    const { data } = await apiClient.get('/orders', { params: { symbol } })
    state.orders = data.data
  } finally {
    state.isLoading = false
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
  if (userChannel) {
    userChannel.stopListening('OrderMatched')
    echo.leave(`private-user.${userId}`)
  }

  userChannel = echo.private(`user.${userId}`)

  userChannel.listen('OrderMatched', (event) => {
    // Update balance directly from event payload
    if (event.balance !== undefined) {
      state.balance = event.balance
    }

    // Update assets directly from event payload
    if (event.assets) {
      event.assets.forEach((updatedAsset) => {
        const index = state.assets.findIndex((a) => a.symbol === updatedAsset.symbol)
        if (index !== -1) {
          state.assets[index] = { ...state.assets[index], ...updatedAsset }
        } else {
          state.assets.push(updatedAsset)
        }
      })
    }

    // Update order status directly from event payload
    if (event.order) {
      const index = state.orders.findIndex((o) => o.id === event.order.id)
      if (index !== -1) {
        state.orders[index] = { ...state.orders[index], ...event.order }
      }
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
  placeOrder,
  cancelOrder,
  subscribeToUserChannel,
  unsubscribeFromUserChannel,
  setAuthToken,
  clearAuth,
}
