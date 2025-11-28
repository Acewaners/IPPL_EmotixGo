<script setup>
import { computed, reactive, ref } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { useCartStore } from '../stores/cart'
import { useRouter, RouterLink } from 'vue-router'
import { api } from '../lib/api'

const cart = useCartStore()
const r = useRouter()

// data cart
const items = computed(() => cart.cartItems)
const subtotal = computed(() => cart.cartSubtotal)
const shipping = computed(() => 0)
const total = computed(() => subtotal.value + shipping.value)

// base URL storage (sama kayak di halaman lain)
const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const productImage = (p) => {
  if (!p) return ''
  if (p.image_full) return p.image_full
  if (p.image_url) return p.image_url
  if (p.image) {
    if (p.image.startsWith('http')) return p.image
    return `${STORAGE_BASE}/${p.image}`
  }
  return ''
}

const formatPrice = (price) =>
  `Rp. ${Number(price || 0).toLocaleString('id-ID')}`

// form billing
const billing = reactive({
  firstName: '',
  company: '',
  address: '',
  address2: '',
  city: '',
  phone: '',
  email: '',
  saveInfo: false,
  paymentMethod: 'qris',
  coupon: '',
})

const placing = ref(false)

const applyCoupon = () => {
  if (!billing.coupon) return
  alert('Coupon ini masih dummy, belum diimplementasi 😊')
}

// --- INI YANG PENTING: kirim transaksi ke backend ---
const placeOrder = async () => {
  if (!items.value.length) {
    return alert('Cart masih kosong, nggak ada yang bisa di-checkout.')
  }

  // validasi simpel billing (biar nggak kosong banget)
  if (
    !billing.firstName ||
    !billing.address ||
    !billing.city ||
    !billing.phone ||
    !billing.email
  ) {
    return alert('Lengkapi dulu data Billing Details ya.')
  }

  // ambil seller_id dari product pertama (asumsi 1 cart = 1 seller)
  const firstItem = items.value[0]
  const sellerId = firstItem?.product?.seller_id

  if (!sellerId) {
    return alert('seller_id tidak ditemukan di product, cek data produk dulu.')
  }

  // bentuk payload sesuai validate() di backend
  const payload = {
    seller_id: sellerId,
    items: items.value.map((i) => ({
      product_id: i.product.product_id,
      quantity: i.quantity,
    })),
  }

  placing.value = true

  try {
    const res = await api.post('/transactions', payload)

    // bersihkan cart setelah transaksi dibuat
    cart.clearCart()

    // redirect ke halaman payment, kirim data transaksi (opsional lewat state)
    r.push({
      name: 'payment', // pastikan di router ada route ini
      params: { id: res.data.transaction_id },
      state: { trx: res.data },
    })
  } catch (e) {
    const resp = e.response

    // error validasi (422) dari backend
    if (resp?.status === 422) {
      const errors = resp.data?.errors
      if (errors) {
        const msg = Object.values(errors).flat().join('\n')
        alert(msg)
      } else {
        alert(resp.data?.message || 'Data tidak valid.')
      }
    } else {
      console.error(e)
      alert('Checkout gagal, coba lagi sebentar lagi ya.')
    }
  } finally {
    placing.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 max-w-6xl mx-auto px-4 lg:px-0 py-10 space-y-8">
      <!-- breadcrumb -->
      <section class="text-xs md:text-sm text-gray-500">
        <RouterLink to="/" class="hover:text-black">Home</RouterLink>
        <span class="mx-1">/</span>
        <RouterLink to="/cart" class="hover:text-black">Cart</RouterLink>
        <span class="mx-1">/</span>
        <span class="text-black font-medium">Checkout</span>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
        <!-- Billing form -->
        <div class="lg:col-span-2">
          <h1 class="text-xl font-semibold mb-6">Billing Details</h1>

          <form class="space-y-4" @submit.prevent="placeOrder">
            <div class="space-y-1">
              <label class="text-xs font-medium text-gray-700">First Name*</label>
              <input
                v-model="billing.firstName"
                type="text"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-medium text-gray-700">Company Name</label>
              <input
                v-model="billing.company"
                type="text"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-medium text-gray-700">Street Address*</label>
              <input
                v-model="billing.address"
                type="text"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-medium text-gray-700">
                Apartment, floor, etc. (optional)
              </label>
              <input
                v-model="billing.address2"
                type="text"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-medium text-gray-700">Town/City*</label>
              <input
                v-model="billing.city"
                type="text"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-medium text-gray-700">Phone Number*</label>
              <input
                v-model="billing.phone"
                type="tel"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-medium text-gray-700">Email Address*</label>
              <input
                v-model="billing.email"
                type="email"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
              />
            </div>

            <label class="mt-3 flex items-center gap-2 text-xs text-gray-600">
              <input
                v-model="billing.saveInfo"
                type="checkbox"
                class="rounded border-gray-300 text-gray-900 focus:ring-gray-800"
              />
              <span>
                Save this information for faster check-out next time
              </span>
            </label>
          </form>
        </div>

        <!-- Order summary -->
        <div class="border rounded-lg p-5 space-y-5">
          <div class="space-y-3 text-sm">
            <div
              v-for="item in items"
              :key="item.product.product_id"
              class="flex items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3">
                <img
                  :src="productImage(item.product)"
                  alt=""
                  class="w-12 h-12 object-cover rounded border"
                />
                <span class="text-xs md:text-sm">
                  {{ item.product.product_name }}
                </span>
              </div>
              <span class="text-xs md:text-sm">
                {{ formatPrice(item.product.price) }}
              </span>
            </div>

            <div class="border-top pt-3 space-y-2 text-xs md:text-sm border-t">
              <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>{{ formatPrice(subtotal) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Shipping:</span>
                <span>Free</span>
              </div>
              <div class="flex justify-between font-semibold pt-1">
                <span>Total:</span>
                <span>{{ formatPrice(total) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment method -->
          <div class="space-y-2 text-xs md:text-sm">
            <p class="font-semibold">Payment Method</p>
            <label class="flex items-center gap-2">
              <input
                v-model="billing.paymentMethod"
                type="radio"
                value="qris"
                class="text-gray-900 focus:ring-gray-800"
              />
              <span>QRIS</span>
            </label>
          </div>

          <!-- Coupon -->
          <div class="flex flex-col gap-2">
            <div class="flex gap-2">
              <input
                v-model="billing.coupon"
                type="text"
                placeholder="Coupon Code"
                class="flex-1 border rounded px-3 py-2 text-xs md:text-sm"
              />
              <button
                type="button"
                @click="applyCoupon"
                class="px-4 py-2 bg-red-500 text-white text-xs md:text-sm rounded hover:bg-red-600"
              >
                Apply Coupon
              </button>
            </div>
          </div>

          <button
            type="button"
            @click="placeOrder"
            :disabled="placing"
            class="w-full bg-red-500 text-white text-xs md:text-sm py-3 rounded mt-2 hover:bg-red-600 disabled:opacity-60"
          >
            {{ placing ? 'Processing…' : 'Place Order' }}
          </button>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
