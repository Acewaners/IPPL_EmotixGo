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
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 bg-gray-50">
      <section class="max-w-6xl mx-auto px-4 lg:px-0 py-8">
        <!-- Breadcrumb -->
        <p class="text-xs text-gray-500 mb-4">
          <span>Home</span>
          <span class="mx-1">/</span>
          <span class="font-medium text-black">Pesanan Saya</span>
        </p>

        <!-- Header -->
        <div class="mb-6">
          <h1 class="text-2xl font-semibold text-gray-900">Pesanan Saya</h1>
          <p class="text-sm text-gray-500">
            Riwayat dan status pesanan anda.
          </p>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 px-4 py-3 rounded-md bg-red-50 text-red-700 text-sm">
          {{ error }}
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-sm text-gray-500">
          Loading...
        </div>

        <!-- List pesanan -->
        <div v-else>
          <!-- Ada pesanan -->
          <div v-if="orders.length" class="space-y-4">
            <div
              v-for="o in orders"
              :key="o.transaction_id"
              class="bg-white border border-gray-200 rounded-lg p-4 md:p-5 shadow-sm"
            >
              <!-- Header order -->
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                  <p class="text-sm font-semibold text-gray-900">
                    Order #{{ o.transaction_id }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ o.transaction_date }}
                  </p>
                </div>

                <div class="text-right">
                  <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                    :class="{
                      'bg-yellow-50 text-yellow-700 border border-yellow-200': o.status === 'pending',
                      'bg-green-50 text-green-700 border border-green-200': o.status === 'completed' || o.status === 'success',
                      'bg-red-50 text-red-700 border border-red-200': o.status === 'cancelled',
                      'bg-gray-100 text-gray-700 border border-gray-200': !['pending','completed','success','cancelled'].includes(o.status)
                    }"
                  >
                    {{ o.status }}
                  </span>
                  <p class="text-sm font-semibold text-gray-900 mt-1">
                    Total: Rp {{ Number(o.total_price).toLocaleString('id-ID') }}
                  </p>
                </div>
              </div>

              <!-- Detail produk -->
              <div class="mt-3 border-t border-gray-100 pt-3">
                <p class="text-xs font-medium text-gray-500 mb-1">
                  Produk
                </p>
                <ul class="space-y-1 text-sm text-gray-700">
                  <li
                    v-for="d in o.details"
                    :key="d.detail_id"
                    class="flex justify-between gap-4"
                  >
                    <span class="line-clamp-1">
                      {{ d.product?.product_name }} × {{ d.quantity }}
                    </span>
                    <span class="font-medium">
                      Rp {{ Number(d.subtotal).toLocaleString('id-ID') }}
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Belum ada pesanan -->
          <div
            v-else
            class="bg-white border border-gray-200 rounded-lg p-6 md:p-8 max-w-xl"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
              Belum ada pesanan
            </h2>
            <p class="text-sm text-gray-500 mb-4">
              Kamu belum pernah melakukan transaksi. Yuk mulai belanja produk
              favoritmu di Emotix.
            </p>
            <button
              class="inline-flex items-center px-4 py-2 rounded-md bg-red-500 text-white text-sm font-semibold hover:bg-red-600"
              @click="$router.push('/')"
            >
              Mulai Belanja
            </button>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
