<script setup>
import { computed, reactive, ref } from 'vue' // Tambah ref
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { useCartStore } from '../stores/cart'
import { useRouter, RouterLink } from 'vue-router'
import api from '../lib/api' // Import API helper

const cart = useCartStore()
const r = useRouter()
const isLoading = ref(false) // State loading

// data cart
const items = computed(() => cart.cartItems)
const subtotal = computed(() => cart.cartSubtotal)
const shipping = computed(() => 0)
const total = computed(() => subtotal.value + shipping.value)

// form billing
const billing = reactive({
  firstName: '',
  company: '',
  address: '',
  address2: '',
  city: '',
  phone: '',
  email: '',
  saveInfo: true,
  paymentMethod: 'qris',
  coupon: '',
})

// base URL storage
const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

// helper gambar produk
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

const applyCoupon = () => {
  if (!billing.coupon.trim()) {
    alert('Masukkan coupon code dulu.')
    return
  }
  alert(`Coupon "${billing.coupon}" belum diimplementasi ya 😊`)
}

// LOGIKA UTAMA CHECKOUT
const placeOrder = async () => {
  if (!items.value.length) return

  // 1. Validasi Form
  if (!billing.firstName || !billing.address || !billing.city || !billing.phone || !billing.email) {
    alert('Lengkapi semua field yang wajib diisi (*).')
    return
  }

  isLoading.value = true

  try {
    // 2. Grouping Items per Seller
    // Karena backend TransactionController butuh 'seller_id' spesifik per transaksi
    const itemsBySeller = {}

    items.value.forEach((item) => {
      const sellerId = item.product.seller_id
      if (!sellerId) {
        throw new Error(`Produk ${item.product.product_name} tidak memiliki data seller yang valid.`)
      }

      if (!itemsBySeller[sellerId]) {
        itemsBySeller[sellerId] = []
      }

      itemsBySeller[sellerId].push({
        product_id: item.product.product_id,
        quantity: item.quantity
      })
    })

    // 3. Buat Request untuk setiap Seller (Promise.all agar paralel)
    // Jika beli dari 2 toko berbeda, akan jadi 2 transaksi
    const orderPromises = Object.keys(itemsBySeller).map((sellerId) => {
      return api.post('/transactions', {
        seller_id: sellerId,
        items: itemsBySeller[sellerId]
      })
    })

    await Promise.all(orderPromises)

    // 4. Sukses
    alert('Order berhasil dibuat! Terima kasih.')
    cart.clearCart() // Kosongkan keranjang (pastikan ada action ini di store cart Anda)
    r.push('/buyer/orders') // Redirect ke halaman list pesanan (pastikan route ini ada)

  } catch (error) {
    console.error(error)
    const msg = error.response?.data?.message || 'Gagal memproses order.'
    alert(`Error: ${msg}`)
  } finally {
    isLoading.value = false
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

            <div class="border-t pt-3 space-y-2 text-xs md:text-sm">
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
            :disabled="isLoading"
            class="w-full bg-red-500 text-white text-xs md:text-sm py-3 rounded mt-2 hover:bg-red-600 disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            {{ isLoading ? 'Processing...' : 'Place Order' }}
          </button>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
