import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '../lib/api'

export const useWishlistStore = defineStore('wishlist', () => {
  const items = ref([])
  const loading = ref(false)

  const count = computed(() => items.value.length)

  const fetchWishlist = async () => {
    loading.value = true
    try {
      const res = await api.get('/wishlist') 
      const data = res.data?.data ?? res.data
      items.value = Array.isArray(data) ? data : []
    } catch (e) {
      console.error("Failed to load wishlist", e)
    } finally {
      loading.value = false
    }
  }

  const addToWishlist = async (product) => {
    const exists = items.value.find(i => i.product_id === product.product_id)
    if (!exists) {
      items.value.push({ product_id: product.product_id, product }) 
    }

    try {
      await api.post('/wishlist', { product_id: product.product_id })
    } catch (e) {
      if (!exists) items.value = items.value.filter(i => i.product_id !== product.product_id)
      alert("Failed to add to wishlist")
    }
  }

  const removeFromWishlist = async (productId) => {
    const oldItems = [...items.value]
    items.value = items.value.filter(i => i.product?.product_id !== productId && i.product_id !== productId)

    try {
      await api.delete(`/wishlist/${productId}`)
    } catch (e) {
      items.value = oldItems 
      alert("Failed to remove from wishlist")
    }
  }

  const isInWishlist = (productId) => {
    return items.value.some(i => i.product?.product_id === productId || i.product_id === productId)
  }

  return { 
    items, 
    count, 
    loading, 
    fetchWishlist, 
    addToWishlist, 
    removeFromWishlist,
    isInWishlist 
  }
})