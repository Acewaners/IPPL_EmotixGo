<script setup>
import { ref, onMounted, computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { api } from '../lib/api'

const orders = ref([])
const loading = ref(true)
const error = ref('')

const selectedOrder = ref(null)

// base url gambar (sama seperti yang lain)
const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const productImage = (p) => {
  if (!p) return ''
  if (p.image_full) return p.image_full
  if (p.image_url) return p.image_url
  if (!p.image) return ''
  if (p.image.startsWith('http')) return p.image
  return `${STORAGE_BASE}/${p.image}`
}

const formatPrice = (v) =>
  `Rp ${Number(v || 0).toLocaleString('id-ID')}`

const formatDateTime = (v) => {
  if (!v) return '-'
  return new Date(v).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'pending_payment':
      return 'bg-yellow-50 text-yellow-700 border border-yellow-200'
    case 'processing':
      return 'bg-blue-50 text-blue-700 border border-blue-200'
    case 'shipped':
      return 'bg-sky-50 text-sky-700 border border-sky-200'
    case 'completed':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'failed':
      return 'bg-red-50 text-red-700 border border-red-200'
    default:
      return 'bg-gray-50 text-gray-600 border border-gray-200'
  }
}

const loadingStatus = ref(null)

async function updateStatus(order, newStatus) {
  const oldStatus = order.status
  loadingStatus.value = order.transaction_id
  
  try {
    await api.put(`/seller/orders/${order.transaction_id}/status`, {
      status: newStatus
    })
    order.status = newStatus // Update di layar jika sukses
  } catch (e) {
    order.status = oldStatus // Kembalikan jika gagal
    alert(e.response?.data?.message || 'Gagal mengubah status')
  } finally {
    loadingStatus.value = null
  }
}

const loadOrders = async () => {
  loading.value = true
  error.value = ''
  try {
    // sesuaikan dengan route backend kamu
    const res = await api.get('/seller/orders')
    // bisa jadi {data: {data: []}} atau {data: []}
    const raw = res.data?.data ?? res.data
    orders.value = Array.isArray(raw?.data) ? raw.data : raw
    if (orders.value.length) {
      selectedOrder.value = orders.value[0]
    }
  } catch (e) {
    console.error(e)
    error.value =
      e?.response?.data?.message || 'Failed to load seller orders.'
  } finally {
    loading.value = false
  }
}

const totalItems = computed(() => {
  if (!selectedOrder.value?.details) return 0
  return selectedOrder.value.details.reduce(
    (sum, d) => sum + Number(d.quantity || 0),
    0
  )
})

const selectOrder = (order) => {
  selectedOrder.value = order
}

onMounted(loadOrders)
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
      
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-gray-900">
            Seller Orders
          </h1>
          <p class="text-sm text-gray-500 mt-1">
            Monitor and manage your incoming orders efficiently.
          </p>
        </div>
        
        <div class="hidden md:flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-gray-200 shadow-sm">
           <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
           <span class="text-sm font-medium text-gray-700">Total Orders: <strong>{{ orders.length }}</strong></span>
        </div>
      </div>

      <section v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-200 border-t-black"></div>
        <p class="mt-4 text-gray-500 text-sm font-medium">Loading your orders...</p>
      </section>

      <section v-else-if="error" class="py-20 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-50 mb-4">
           <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900">Something went wrong</h3>
        <p class="text-gray-500 mt-1">{{ error }}</p>
        <button @click="loadOrders" class="mt-4 px-6 py-2 bg-black text-white rounded-full text-sm font-medium hover:bg-gray-800 transition-colors">Try Again</button>
      </section>

      <section
        v-else
        class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start"
      >
        
        <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col min-h-[500px]">
          <div class="px-6 py-5 border-b border-gray-100 bg-white flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-900">Order List</h2>
            <span class="md:hidden text-xs bg-gray-100 px-2 py-1 rounded-full text-gray-600 font-bold">{{ orders.length }}</span>
          </div>

          <div v-if="!orders.length" class="flex-1 flex flex-col items-center justify-center py-20 text-center">
             <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
               <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
             </div>
             <p class="text-gray-500 text-sm">No orders found.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
              <thead class="bg-gray-50/50 text-xs text-gray-400 font-bold uppercase tracking-wider">
                <tr>
                  <th class="px-6 py-4">Order ID</th>
                  <th class="px-6 py-4">Customer</th>
                  <th class="px-6 py-4">Amount</th>
                  <th class="px-6 py-4">Date</th>
                  <th class="px-6 py-4">Status</th>
                  <th class="px-6 py-4 text-right"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="o in orders"
                  :key="o.transaction_id"
                  @click="selectOrder(o)"
                  class="group transition-all cursor-pointer"
                  :class="{
                    'bg-blue-50/60 hover:bg-blue-50': selectedOrder && selectedOrder.transaction_id === o.transaction_id,
                    'hover:bg-gray-50': !selectedOrder || selectedOrder.transaction_id !== o.transaction_id
                  }"
                >
                  <td class="px-6 py-4 font-mono text-xs font-medium text-gray-500 group-hover:text-black transition-colors">
                    #ORD-{{ o.transaction_id }}
                  </td>

                  <td class="px-6 py-4">
                    <div class="flex flex-col">
                      <span class="font-semibold text-gray-900">{{ o.buyer?.name || 'Customer #' + o.buyer_id }}</span>
                      <span class="text-xs text-gray-400">{{ o.buyer?.email || 'Contact info unavailable' }}</span>
                    </div>
                  </td>

                  <td class="px-6 py-4 font-medium text-gray-900">
                    {{ formatPrice(o.total_price) }}
                  </td>

                  <td class="px-6 py-4 text-xs text-gray-500">
                    {{ formatDateTime(o.transaction_date || o.created_at) }}
                  </td>

                  <td class="px-6 py-4" @click.stop> <select
                      :value="o.status"
                      @change="updateStatus(o, $event.target.value)"
                      :disabled="loadingStatus === o.transaction_id"
                      class="block w-full rounded-md border-0 py-1 px-2 text-xs font-bold ring-1 ring-inset focus:ring-2 cursor-pointer transition-all"
                      :class="statusBadgeClass(o.status)"
                    >
                      <option value="pending">Pending</option>
                      <option value="processing">Processing</option>
                      <option value="shipped">Shipped</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </td>

                  <td class="px-6 py-4 text-right">
                    <button class="w-8 h-8 rounded-full flex items-center justify-center border border-gray-200 text-gray-400 group-hover:border-black group-hover:text-black group-hover:bg-white transition-all shadow-sm">
                       <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                          <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="lg:col-span-1 sticky top-28">
           
           <div v-if="!selectedOrder" class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 text-center h-[500px] flex flex-col items-center justify-center">
              <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6 text-gray-300">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                 </svg>
              </div>
              <h3 class="text-gray-900 font-bold mb-2">Order Details</h3>
              <p class="text-gray-500 text-sm">Select an order from the list to view full details.</p>
           </div>

           <div v-else class="bg-white rounded-3xl border border-gray-100 shadow-lg overflow-hidden flex flex-col">
              
              <div class="px-6 py-5 bg-gray-50/50 border-b border-gray-100">
                 <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-gray-900">Order Details</h3>
                    <span class="font-mono text-xs text-gray-500 bg-white px-2 py-1 rounded border">#ORD-{{ selectedOrder.transaction_id }}</span>
                 </div>
                 <div class="flex items-center gap-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    {{ formatDateTime(selectedOrder.transaction_date || selectedOrder.created_at) }}
                 </div>
              </div>

              <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-4">
                 <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                 </div>
                 <div>
                    <p class="text-sm font-bold text-gray-900">{{ selectedOrder.buyer?.name || 'Customer' }}</p>
                    <p class="text-xs text-gray-500">{{ selectedOrder.buyer?.email || 'N/A' }}</p>
                 </div>
              </div>

              <div class="px-6 py-4 bg-white flex-1 overflow-y-auto max-h-[350px]">
                 <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Items ({{ totalItems }})</h4>
                 
                 <div class="space-y-4">
                    <div
                      v-for="d in selectedOrder.details || []"
                      :key="d.detail_id || d.item_id"
                      class="flex gap-3"
                    >
                       <div class="w-14 h-14 rounded-lg bg-gray-50 border border-gray-100 p-1 flex-shrink-0">
                          <img
                             :src="productImage(d.product)"
                             alt="Product"
                             class="w-full h-full object-contain mix-blend-multiply"
                          />
                       </div>
                       
                       <div class="flex-1 min-w-0">
                          <p class="text-sm font-semibold text-gray-900 line-clamp-1">
                             {{ d.product?.product_name || 'Unknown Product' }}
                          </p>
                          <div class="flex justify-between items-center mt-1">
                             <p class="text-xs text-gray-500">
                                {{ d.quantity }} x {{ formatPrice(d.product?.price || d.price || 0) }}
                             </p>
                             <p class="text-sm font-medium text-gray-900">
                                {{ formatPrice(d.subtotal || (d.quantity * (d.product?.price || d.price || 0))) }}
                             </p>
                          </div>
                       </div>
                    </div>

                    <div v-if="!(selectedOrder.details || []).length" class="text-center py-4 text-xs text-gray-400 italic">
                      No items found in this order.
                    </div>
                 </div>
              </div>

              <div class="px-6 py-5 bg-gray-50 border-t border-gray-100 space-y-2">
                 <div class="flex justify-between text-sm text-gray-500">
                    <span>Subtotal</span>
                    <span>{{ formatPrice(selectedOrder.total_price) }}</span>
                 </div>
                 <div class="flex justify-between text-sm text-gray-500">
                    <span>Shipping</span>
                    <span class="text-green-600 font-medium">Free</span>
                 </div>
                 <div class="border-t border-gray-200 my-2"></div>
                 <div class="flex justify-between items-center">
                    <span class="text-sm font-bold text-gray-900">Total</span>
                    <span class="text-lg font-extrabold text-gray-900">{{ formatPrice(selectedOrder.total_price) }}</span>
                 </div>
                 
                 <div class="pt-4">
                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-2 block">Update Order Status</label>
                    <select
                      :value="selectedOrder.status"
                      @change="updateStatus(selectedOrder, $event.target.value)"
                      :disabled="loadingStatus === selectedOrder.transaction_id"
                      class="w-full py-2.5 rounded-xl text-center text-xs font-bold border uppercase tracking-wider cursor-pointer"
                      :class="statusBadgeClass(selectedOrder.status)"
                    >
                      <option value="pending">Pending</option>
                      <option value="processing">Processing</option>
                      <option value="shipped">Shipped</option>
                      <option value="completed">Completed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                    <p v-if="loadingStatus === selectedOrder.transaction_id" class="text-[10px] text-center mt-1 animate-pulse">Updating...</p>
                  </div>
              </div>

           </div>
        </div>

      </section>
    </main>

    <Footer />
  </div>
</template>
