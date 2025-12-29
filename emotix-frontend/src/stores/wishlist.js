import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '../lib/api'

export const useWishlistStore = defineStore('wishlist', () => {
  const items = ref([])
  const loading = ref(false)

  // Getter: Hitung jumlah item
  const count = computed(() => items.value.length)

  // Action: Ambil data dari server (dipanggil saat aplikasi mulai)
  const fetchWishlist = async () => {
    loading.value = true
    try {
      const res = await api.get('/wishlist') // Sesuaikan endpoint backend Anda
      const data = res.data?.data ?? res.data
      items.value = Array.isArray(data) ? data : []
    } catch (e) {
      console.error("Gagal load wishlist", e)
    } finally {
      loading.value = false
    }
  }

  // Action: Tambah item (Cek dulu biar gak duplikat di Frontend)
  const addToWishlist = async (product) => {
    // Optimistic Update: Tambah dulu ke layar biar cepat
    const exists = items.value.find(i => i.product_id === product.product_id)
    if (!exists) {
      items.value.push({ product_id: product.product_id, product }) 
    }

    try {
      await api.post('/wishlist', { product_id: product.product_id })
      // Opsional: fetch ulang untuk memastikan sinkron
      // await fetchWishlist() 
    } catch (e) {
      // Kalau gagal, kembalikan (Rollback)
      if (!exists) items.value = items.value.filter(i => i.product_id !== product.product_id)
      alert("Gagal menambah ke wishlist")
    }
  }

  // Action: Hapus item
  const removeFromWishlist = async (productId) => {
    // Optimistic Update
    const oldItems = [...items.value]
    items.value = items.value.filter(i => i.product?.product_id !== productId && i.product_id !== productId)

    try {
      await api.delete(`/wishlist/${productId}`)
    } catch (e) {
      items.value = oldItems // Rollback
      alert("Gagal menghapus")
    }
  }

  // Cek apakah produk tertentu ada di wishlist
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