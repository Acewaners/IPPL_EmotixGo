<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { api } from '../lib/api'

const route = useRoute()
const router = useRouter()

// STATE
const order = ref(null)
const loading = ref(true)
const error = ref('')
const status = ref('waiting')       // waiting | success | failed
const remaining = ref(10)           // 10 detik
const updating = ref(false)
let timerId = null

// HELPER
const formatPrice = (v) =>
  `Rp ${Number(v || 0).toLocaleString('id-ID')}`

const orderNumber = computed(
  () =>
    order.value?.order_number ??
    order.value?.transaction_id ??
    '-'
)

const totalPayment = computed(
  () => order.value?.total_price ?? 0
)

// AMBIL DATA TRANSAKSI
const loadOrder = async () => {
  loading.value = true
  error.value = ''
  try {
    const id = route.params.id
    const res = await api.get(`/transactions/${id}`)
    // tergantung response backend: {data: {...}} atau {...}
    order.value = res.data?.data ?? res.data
  } catch (e) {
    console.error(e)
    error.value =
      e?.response?.data?.message || 'Failed to load transaction.'
  } finally {
    loading.value = false
  }
}

// UPDATE STATUS JADI COMPLETED
const updateStatusToCompleted = async () => {
  if (!order.value) return

  updating.value = true
  error.value = ''

  try {
    const id = order.value.transaction_id
    await api.post(`/transactions/${id}/status`, {
      status: 'completed',
      tracking_number: null,
    })
    status.value = 'success'
    // kalau mau, redirect ke halaman success:
    // router.push({ name: 'payment-success', params: { id } })
  } catch (e) {
    console.error(e)
    status.value = 'failed'
    error.value =
      e?.response?.data?.message ||
      'Failed to update payment status.'
  } finally {
    updating.value = false
  }
}

// dipakai kalau user klik tombol "Simulate Payment (Demo)"
const simulatePayment = async () => {
  await updateStatusToCompleted()
}

// TIMER 10 DETIK AUTO BAYAR
const startCountdown = () => {
  timerId = setInterval(async () => {
    if (remaining.value > 0) {
      remaining.value -= 1
    } else {
      clearInterval(timerId)
      await updateStatusToCompleted()
    }
  }, 1000)
}

onMounted(async () => {
  await loadOrder()
  if (!error.value) {
    startCountdown()
  }
})

onBeforeUnmount(() => {
  if (timerId) clearInterval(timerId)
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <Navbar />

    <main class="flex-1 flex justify-center py-10 px-4">
      <!-- loading -->
      <div
        v-if="loading"
        class="max-w-3xl w-full bg-white rounded-xl shadow p-8 text-center text-gray-500"
      >
        Loading payment...
      </div>

      <!-- content -->
      <div
        v-else
        class="max-w-3xl w-full bg-white rounded-xl shadow p-8 space-y-6"
      >
        <!-- Header -->
        <div class="text-center space-y-1">
          <h1 class="text-xl font-semibold">
            Complete Your Payment
          </h1>
          <p class="text-sm text-gray-500">
            Scan the QR code below to complete your payment
          </p>
        </div>

        <!-- Countdown -->
        <div
          class="bg-yellow-50 border border-yellow-200 text-xs md:text-sm text-yellow-800 px-4 py-3 rounded flex justify-between"
        >
          <span>Complete payment within:</span>
          <span class="font-semibold">
            00:{{ remaining.toString().padStart(2, '0') }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start text-sm">
          <!-- Left: detail -->
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-500">Order Number:</span>
              <span class="font-mono text-xs md:text-sm">
                {{ orderNumber }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Payment Method:</span>
              <span>QRIS</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Total Payment:</span>
              <span class="text-red-500 font-semibold">
                {{ formatPrice(totalPayment) }}
              </span>
            </div>

            <!-- error dari backend -->
            <div
              v-if="error"
              class="mt-2 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-700 text-xs md:text-sm"
            >
              {{ error }}
            </div>

            <!-- status info -->
            <div
              v-else-if="status === 'success'"
              class="mt-2 px-3 py-2 rounded bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs md:text-sm"
            >
              Payment successful! Your order is being processed.
            </div>
            <div
              v-else-if="status === 'failed'"
              class="mt-2 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-700 text-xs md:text-sm"
            >
              Failed to update payment status.
            </div>
          </div>

          <!-- Right: QR -->
          <div class="border rounded-xl p-6 flex flex-col items-center gap-4">
            <div
              class="w-48 h-48 bg-gray-100 flex items-center justify-center rounded-lg"
            >
              <img
                src="/dummy-qr.png"
                alt="QR Code"
                class="w-40 h-40 object-contain"
              />
            </div>
            <p class="text-xs md:text-sm text-gray-500 text-center">
              Scan QR Code with your banking app<br />
              or e-wallet that supports QRIS payment
            </p>
          </div>
        </div>

        <!-- Instructions -->
        <div
          class="bg-gray-50 border rounded-lg p-4 text-xs md:text-sm space-y-1"
        >
          <p class="font-semibold">Payment Instructions:</p>
          <ol class="list-decimal list-inside space-y-1 text-gray-600">
            <li>Open your mobile banking or e-wallet app</li>
            <li>Select "Scan QR" or "QRIS" menu</li>
            <li>Scan the QR code displayed above</li>
            <li>Verify the payment amount matches the total</li>
            <li>Complete the payment in your app</li>
          </ol>
        </div>

        <!-- Demo button -->
        <div class="pt-2">
          <button
            type="button"
            :disabled="updating"
            @click="simulatePayment"
            class="w-full border rounded-md py-2 text-xs md:text-sm flex items-center justify-center gap-2 hover:bg-gray-50 disabled:opacity-60"
          >
            {{ updating ? 'Processing...' : 'Simulate Payment (Demo)' }}
          </button>
        </div>

        <div class="text-center text-xs text-gray-400">
          This is a demo button. In production, payment will be verified
          automatically.
        </div>

        <div class="text-center text-xs text-gray-500">
          Having trouble?
          <button
            type="button"
            class="text-emerald-600 hover:underline"
            @click="router.push('/contact')"
          >
            Contact Support
          </button>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
