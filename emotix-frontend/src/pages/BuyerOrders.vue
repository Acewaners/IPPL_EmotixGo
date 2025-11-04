<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../lib/api'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'
import Shell from './Shell.vue'

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
  <Shell>
    <h1 class="text-xl font-semibold mb-2">Pesanan Saya</h1>
    <p class="text-sm text-gray-500 mb-4">Riwayat dan status pesanan.</p>

    <div v-if="loading">Loading...</div>
    <div v-if="error" class="text-red-600">{{ error }}</div>

    <div v-if="!loading && orders.length" class="space-y-4">
      <div v-for="o in orders" :key="o.transaction_id" class="border rounded p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium">#{{ o.transaction_id }} • {{ o.status }}</p>
            <p class="text-sm text-gray-500">Total: Rp {{ Number(o.total_price).toLocaleString('id-ID') }}</p>
          </div>
          <div class="text-xs text-gray-500">{{ o.transaction_date }}</div>
        </div>

        <ul class="mt-2 list-disc pl-5 text-sm">
          <li v-for="d in o.details" :key="d.detail_id">
            {{ d.product?.product_name }} × {{ d.quantity }} = Rp {{ Number(d.subtotal).toLocaleString('id-ID') }}
          </li>
        </ul>
      </div>
    </div>

    <p v-else-if="!loading" class="text-gray-500">Belum ada pesanan.</p>
  </Shell>
</template>
