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
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 max-w-6xl mx-auto px-4 lg:px-0 py-10 space-y-8">
      <!-- Header -->
      <section class="space-y-1">
        <h1 class="text-xl font-semibold">Seller Orders</h1>
        <p class="text-sm text-gray-500">
          Lihat semua pesanan yang masuk ke toko kamu, sayang 💚
        </p>
      </section>

      <!-- State: loading / error -->
      <section v-if="loading" class="py-10 text-center text-gray-500 text-sm">
        Loading orders...
      </section>

      <section v-else-if="error" class="py-10 text-center text-red-500 text-sm">
        {{ error }}
      </section>

      <!-- Main content -->
      <section
        v-else
        class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start"
      >
        <!-- Left: Orders table -->
        <div class="lg:col-span-2 border rounded-xl overflow-hidden">
          <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold">All Orders</h2>
            <span class="text-xs text-gray-500">
              {{ orders.length }} order(s)
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 border-b text-xs text-gray-500">
                <tr>
                  <th class="px-4 py-3 text-left">Order ID</th>
                  <th class="px-4 py-3 text-left">Customer</th>
                  <th class="px-4 py-3 text-left">Amount</th>
                  <th class="px-4 py-3 text-left">Date</th>
                  <th class="px-4 py-3 text-left">Status</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-if="!orders.length"
                  class="text-center text-gray-500 text-xs"
                >
                  <td colspan="6" class="px-4 py-6">
                    Belum ada order untuk ditampilkan.
                  </td>
                </tr>

                <tr
                  v-for="o in orders"
                  :key="o.transaction_id"
                  class="border-b hover:bg-gray-50 cursor-pointer"
                  :class="{
                    'bg-gray-50/70':
                      selectedOrder &&
                      selectedOrder.transaction_id === o.transaction_id,
                  }"
                  @click="selectOrder(o)"
                >
                  <td class="px-4 py-3 align-top">
                    <div class="font-medium text-xs md:text-sm">
                      #ORD-{{ o.transaction_id }}
                    </div>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <div class="text-xs md:text-sm">
                      {{ o.buyer?.name || '—' }}
                    </div>
                    <div class="text-[11px] text-gray-400">
                      {{ o.buyer?.email || '' }}
                    </div>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <span class="text-xs md:text-sm">
                      {{ formatPrice(o.total_price) }}
                    </span>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <span class="text-xs md:text-sm text-gray-500">
                      {{ formatDateTime(o.transaction_date || o.created_at) }}
                    </span>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <span
                      class="inline-flex items-center px-2 py-1 rounded-full text-[11px] md:text-xs"
                      :class="statusBadgeClass(o.status)"
                    >
                      {{ o.status }}
                    </span>
                  </td>

                  <td class="px-4 py-3 align-top text-right">
                    <button
                      type="button"
                      class="px-3 py-1.5 text-xs rounded-md border bg-white hover:bg-gray-50"
                      @click.stop="selectOrder(o)"
                    >
                      View
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right: Detail panel -->
        <div class="border rounded-xl p-5 space-y-5">
          <h2 class="text-sm font-semibold mb-1">Order Details</h2>

          <div v-if="!selectedOrder" class="text-xs text-gray-500">
            Pilih salah satu order di tabel untuk melihat detailnya.
          </div>

          <div v-else class="space-y-4 text-xs md:text-sm">
            <!-- top info -->
            <div class="space-y-1">
              <div class="flex justify-between">
                <span class="text-gray-500">Order ID</span>
                <span class="font-mono text-xs md:text-sm">
                  #ORD-{{ selectedOrder.transaction_id }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Date</span>
                <span>
                  {{
                    formatDateTime(
                      selectedOrder.transaction_date ||
                        selectedOrder.created_at
                    )
                  }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Customer</span>
                <span>
                  {{ selectedOrder.buyer?.name || '—' }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Status</span>
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-[11px]"
                  :class="statusBadgeClass(selectedOrder.status)"
                >
                  {{ selectedOrder.status }}
                </span>
              </div>
            </div>

            <!-- items -->
            <div class="border-t pt-3 space-y-2">
              <p class="text-xs font-semibold text-gray-700 mb-1">
                Items ({{ totalItems }})
              </p>

              <div
                v-for="d in selectedOrder.details || []"
                :key="d.detail_id || d.item_id"
                class="flex items-center justify-between gap-3 py-2"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 rounded border bg-gray-50 overflow-hidden flex items-center justify-center"
                  >
                    <img
                      :src="productImage(d.product)"
                      alt=""
                      class="w-full h-full object-cover"
                    />
                  </div>
                  <div>
                    <p class="text-xs md:text-sm font-medium">
                      {{ d.product?.product_name || 'Unknown Product' }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                      Qty: {{ d.quantity }} ×
                      {{ formatPrice(d.product?.price || d.price || 0) }}
                    </p>
                  </div>
                </div>
                <div class="text-xs md:text-sm font-medium">
                  {{ formatPrice(d.subtotal || (d.quantity * (d.product?.price || d.price || 0))) }}
                </div>
              </div>

              <div v-if="!(selectedOrder.details || []).length" class="text-xs text-gray-400">
                Tidak ada item detail pada order ini.
              </div>
            </div>

            <!-- summary -->
            <div class="border-t pt-3 space-y-1 text-xs md:text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Subtotal</span>
                <span>{{ formatPrice(selectedOrder.total_price) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Shipping</span>
                <span>Free</span>
              </div>
              <div class="flex justify-between font-semibold">
                <span>Total</span>
                <span>{{ formatPrice(selectedOrder.total_price) }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
