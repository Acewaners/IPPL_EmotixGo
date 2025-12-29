<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../lib/api'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'

const auth = useAuth()
const r = useRouter()

const orders = ref([])
const loading = ref(true)
const error = ref('')

const STORAGE_BASE = import.meta.env.VITE_STORAGE_BASE || 'http://localhost:8000/storage'

const getProductImage = (product) => {
  if (!product || !product.image) return '/dummy-qr.png' // Gambar default jika kosong
  
  // Jika image sudah berupa URL lengkap (Cloudinary/http), langsung pakai
  if (product.image.startsWith('http')) {
    return product.image
  }
  
  // Jika path lokal, tambahkan prefix storage
  return `${STORAGE_BASE}/${product.image}`
}

onMounted(async () => {
  if (!auth.token) return r.push('/login')
  if (auth.user?.role !== 'buyer') return r.push('/seller/products')

  try {
    const { data } = await api.get('/buyer/orders')
    orders.value = data?.data ?? []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Gagal memuat pesanan'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <div class="mb-8">
        <nav class="text-sm text-gray-500 mb-4 flex items-center gap-2">
          <span class="hover:text-black transition-colors cursor-pointer" @click="$router.push('/')">Home</span>
          <span class="text-gray-300">/</span>
          <span class="font-semibold text-black">My Orders</span>
        </nav>
        <h1 class="text-3xl font-black tracking-tight text-gray-900">Pesanan Saya</h1>
        <p class="text-gray-500 mt-1">Riwayat lengkap belanjaan kamu di Emotix.</p>
      </div>

      <div v-if="error" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-center gap-3 text-red-700">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 shrink-0"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-medium">{{ error }}</span>
      </div>

      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-black"></div>
        <p class="mt-4 text-sm text-gray-500">Memuat riwayat pesanan...</p>
      </div>

      <div v-else>
        
        <div v-if="orders.length" class="space-y-6">
          <div
            v-for="o in orders"
            :key="o.transaction_id"
            class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300"
          >
            <div class="bg-gray-50/50 px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex flex-col gap-1">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Order ID</p>
                <p class="font-mono text-sm font-medium text-gray-900">#{{ o.transaction_id }}</p>
              </div>
              <div class="flex flex-col gap-1 sm:text-right">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Date</p>
                <p class="text-sm text-gray-600">{{ o.transaction_date }}</p>
              </div>
              <div class="flex flex-col gap-1 sm:text-right">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Amount</p>
                <p class="text-base font-bold text-gray-900">Rp {{ Number(o.total_price).toLocaleString('id-ID') }}</p>
              </div>
              <div class="flex items-center sm:justify-end">
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold capitalize border"
                  :class="{
                    'bg-yellow-50 text-yellow-700 border-yellow-200': o.status === 'pending',
                    'bg-green-50 text-green-700 border-green-200': o.status === 'completed' || o.status === 'success',
                    'bg-red-50 text-red-700 border-red-200': o.status === 'cancelled',
                    'bg-gray-100 text-gray-600 border-gray-200': !['pending','completed','success','cancelled'].includes(o.status)
                  }"
                >
                  <span class="w-1.5 h-1.5 rounded-full mr-2 bg-current opacity-50"></span>
                  {{ o.status }}
                </span>
              </div>
            </div>

            <div class="px-6 py-4">
              <ul class="divide-y divide-gray-50">
                <li
                  v-for="d in o.details"
                  :key="d.detail_id"
                  class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                  <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0 overflow-hidden">
                      <img 
                        v-if="d.product" 
                        :src="getProductImage(d.product)" 
                        :alt="d.product.product_name"
                        class="w-full h-full object-cover mix-blend-multiply"
                      />
                      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div>
                      <p class="font-medium text-sm text-gray-900">{{ d.product?.product_name }}</p>
                      <p class="text-xs text-gray-500">Qty: {{ d.quantity }}x</p>
                    </div>
                  </div>
                  <span class="font-medium text-sm text-gray-900 text-right">
                    Rp {{ Number(d.subtotal).toLocaleString('id-ID') }}
                  </span>
                </li>
              </ul>
            </div>
            
            </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-dashed border-gray-200 text-center">
          <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-gray-400">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 5c.07.286.074.58.074.857 0 .888-.363 1.633-.99 2.127C19.33 17.22 18.239 17.5 17.001 17.5c-1.296 0-2.396-.328-3.085-1.077-.13-.141-.19-.315-.19-.505v-4.257c0-.65-.526-1.168-1.185-1.168-1.184.282-1.854.282-2.924 0-.663 0-1.196.526-1.196 1.175v4.25c0 .185-.058.356-.182.493C7.576 17.16 6.47 17.5 5.166 17.5c-1.229 0-2.311-.272-2.909-.99-.62-.486-.98-1.221-.98-2.092 0-.295.008-.61.085-.92l1.378-5.32c.18-.696.88-1.177 1.597-1.177h12.72c.71 0 1.408.473 1.584 1.178z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-1">Belum ada pesanan</h3>
          <p class="text-gray-500 text-sm mb-6 max-w-xs mx-auto">
            Kamu belum pernah melakukan transaksi. Yuk mulai belanja produk favoritmu di Emotix.
          </p>
          <button
            @click="$router.push('/')"
            class="bg-black text-white px-8 py-3 rounded-full text-sm font-bold shadow-lg hover:bg-gray-800 hover:shadow-xl transition-all active:scale-95"
          >
            Mulai Belanja
          </button>
        </div>

      </div>
    </main>

    <Footer />
  </div>
</template>