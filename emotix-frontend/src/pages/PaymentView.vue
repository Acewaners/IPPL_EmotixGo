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
const remaining = ref(3600)           // 10 detik
const updating = ref(false)
let timerId = null

const formattedTime = computed(() => {
  const hours = Math.floor(remaining.value / 3600)
  const minutes = Math.floor((remaining.value % 3600) / 60)
  const seconds = remaining.value % 60
  
  const h = hours.toString().padStart(2, '0')
  const m = minutes.toString().padStart(2, '0')
  const s = seconds.toString().padStart(2, '0')
  
  return `${h}:${m}:${s}`
})
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
      status: 'processing',
      tracking_number: null,
    })
    // status.value = 'success'
    // kalau mau, redirect ke halaman success:
    // router.push({ name: 'payment-success', params: { id } })
    router.push({ name: 'payment-success' })
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
  <div class="min-h-screen flex flex-col bg-gray-50 font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 flex flex-col items-center justify-center py-12 px-4 sm:px-6">
      
      <div
        v-if="loading"
        class="max-w-xl w-full bg-white rounded-3xl shadow-sm border border-gray-100 p-12 flex flex-col items-center justify-center text-center"
      >
        <div class="w-10 h-10 border-4 border-gray-200 border-t-black rounded-full animate-spin mb-4"></div>
        <p class="text-gray-500 font-medium">Processing payment details...</p>
      </div>

      <div
        v-else
        class="max-w-4xl w-full bg-white rounded-3xl shadow-xl shadow-gray-100/50 border border-gray-100 overflow-hidden"
      >
        <div class="bg-white p-8 text-center border-b border-gray-50">
          <div class="inline-flex items-center justify-center w-12 h-12 bg-black text-white rounded-full mb-4 shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h1 class="text-2xl font-black tracking-tight text-gray-900">
            Complete Payment
          </h1>
          <p class="text-sm text-gray-500 mt-2">
            Please scan the QRIS code to finalize your transaction.
          </p>
        </div>

        <div class="bg-amber-50 border-y border-amber-100 px-6 py-3 flex justify-between items-center">
          <div class="flex items-center gap-2 text-amber-800 text-sm font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 animate-pulse">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
            </svg>
            <span>Complete payment within</span>
          </div>
          <span class="font-mono font-bold text-amber-900 bg-amber-100 px-2 py-1 rounded text-sm">
            {{ formattedTime }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2">
          
          <div class="p-8 space-y-6 border-b md:border-b-0 md:border-r border-gray-100">
            <div>
              <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Payment</p>
              <p class="text-3xl font-black text-gray-900 tracking-tight">
                {{ formatPrice(totalPayment) }}
              </p>
            </div>

            <div class="space-y-4 pt-4">
              <div class="flex justify-between items-center py-3 border-t border-gray-50">
                <span class="text-sm text-gray-500">Order ID</span>
                <span class="font-mono text-sm font-medium bg-gray-100 px-2 py-1 rounded text-gray-700">
                  {{ orderNumber }}
                </span>
              </div>
              <div class="flex justify-between items-center py-3 border-t border-gray-50">
                <span class="text-sm text-gray-500">Method</span>
                <span class="text-sm font-bold text-gray-900 flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-black"></span> QRIS
                </span>
              </div>
            </div>

            <div class="pt-4">
              <div
                v-if="error"
                class="flex items-center gap-3 p-4 rounded-xl bg-red-50 text-red-700 border border-red-100"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 shrink-0"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                <span class="text-sm font-medium">{{ error }}</span>
              </div>

              <div
                v-else-if="status === 'success'"
                class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 shrink-0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                <span class="text-sm font-medium">Payment successful! Redirecting...</span>
              </div>

              <div
                v-else-if="status === 'failed'"
                class="flex items-center gap-3 p-4 rounded-xl bg-red-50 text-red-700 border border-red-100"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 shrink-0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                <span class="text-sm font-medium">Failed to update payment status.</span>
              </div>
            </div>
          </div>

          <div class="p-8 bg-gray-50/50 flex flex-col items-center justify-center">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-6 relative group">
              <div class="absolute top-3 left-3 w-4 h-4 border-t-2 border-l-2 border-black rounded-tl-lg"></div>
              <div class="absolute top-3 right-3 w-4 h-4 border-t-2 border-r-2 border-black rounded-tr-lg"></div>
              <div class="absolute bottom-3 left-3 w-4 h-4 border-b-2 border-l-2 border-black rounded-bl-lg"></div>
              <div class="absolute bottom-3 right-3 w-4 h-4 border-b-2 border-r-2 border-black rounded-br-lg"></div>
              
              <img
                src="/dummy-qr.png"
                alt="QR Code"
                class="w-48 h-48 object-contain mix-blend-multiply"
              />
            </div>
            
            <div class="text-center space-y-4 max-w-xs">
              <p class="text-sm font-medium text-gray-900">Scan via Mobile Banking / E-Wallet</p>
              
              <div class="bg-white border border-gray-200 rounded-xl p-4 text-left">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Instructions</p>
                <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1.5">
                  <li>Open your payment app.</li>
                  <li>Select "Scan QR" / "Pay".</li>
                  <li>Scan the code above.</li>
                  <li>Confirm total & pay.</li>
                </ol>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-gray-50 border-t border-gray-100 p-6 flex flex-col items-center gap-4">
          <button
            type="button"
            :disabled="updating"
            @click="simulatePayment"
            class="w-full max-w-sm bg-black text-white px-6 py-3 rounded-full text-sm font-bold shadow-lg hover:bg-gray-800 hover:shadow-xl hover:-translate-y-0.5 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
          >
            {{ updating ? 'Processing...' : 'Simulate Payment (Demo)' }}
          </button>
          
          <p class="text-xs text-gray-400 text-center">
            Having trouble? 
            <button @click="router.push('/contact')" class="text-black font-semibold hover:underline">Contact Support</button>
          </p>
        </div>

      </div>
    </main>

    <Footer />
  </div>
</template>
