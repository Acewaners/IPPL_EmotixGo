<script setup>
import { computed, reactive, ref } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { useCartStore } from '../stores/cart'
import { useRouter, RouterLink } from 'vue-router'
import { api } from '../lib/api'

const cart = useCartStore()
const r = useRouter()

const items = computed(() => cart.cartItems)
const subtotal = computed(() => cart.cartSubtotal)
const shipping = computed(() => 0)
const total = computed(() => subtotal.value + shipping.value)

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
  alert('This coupon is still dummy, not implemented yet 😊')
}

const placeOrder = async () => {
  if (!items.value.length) {
    return alert('Cart is still empty, please add some products first.')
  }

  if (
    !billing.firstName ||
    !billing.address ||
    !billing.city ||
    !billing.phone
  ) {
    return alert('Please fill in all required billing details.')
  }

  const firstItem = items.value[0]
  const sellerId = firstItem?.product?.seller_id

  if (!sellerId) {
    return alert('Unable to determine seller for the products in the cart.')
  }

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

    cart.clearCart()

    r.push({
      name: 'payment', 
      params: { id: res.data.transaction_id },
      state: { trx: res.data },
    })
  } catch (e) {
    const resp = e.response

    if (resp?.status === 422) {
      const errors = resp.data?.errors
      if (errors) {
        const msg = Object.values(errors).flat().join('\n')
        alert(msg)
      } else {
        alert(resp.data?.message || 'Invalid data provided.')
      }
    } else {
      console.error(e)
      alert('Checkout failed, please try again later.')
    }
  } finally {
    placing.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
      
      <nav class="flex items-center text-sm text-gray-500 mb-10">
        <RouterLink to="/" class="hover:text-black transition-colors">Home</RouterLink>
        <span class="mx-2 text-gray-300">/</span>
        <RouterLink to="/cart" class="hover:text-black transition-colors">Cart</RouterLink>
        <span class="mx-2 text-gray-300">/</span>
        <span class="text-black font-medium">Checkout</span>
      </nav>

      <section class="grid grid-cols-1 lg:grid-cols-12 gap-10 xl:gap-16 items-start">
        
        <div class="lg:col-span-7">
          <h1 class="text-3xl font-bold tracking-tight mb-8 text-gray-900">Billing Details</h1>

          <form id="checkout-form" class="space-y-6" @submit.prevent="placeOrder">
            
            <div class="space-y-2">
              <label class="text-sm font-semibold text-gray-700">Full Name <span class="text-red-500">*</span></label>
              <input
                v-model="billing.firstName"
                type="text"
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                placeholder="Enter your full name"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-semibold text-gray-700">Company Name</label>
              <input
                v-model="billing.company"
                type="text"
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                placeholder="Company name (optional)"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-semibold text-gray-700">Street Address <span class="text-red-500">*</span></label>
              <input
                v-model="billing.address"
                type="text"
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                placeholder="House number and street name"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-semibold text-gray-700">Apartment, floor, etc. (optional)</label>
              <input
                v-model="billing.address2"
                type="text"
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                placeholder="Apartment, suite, unit, etc."
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-semibold text-gray-700">Town / City <span class="text-red-500">*</span></label>
              <input
                v-model="billing.city"
                type="text"
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                placeholder="Enter your city"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-semibold text-gray-700">Phone Number <span class="text-red-500">*</span></label>
              <input
                v-model="billing.phone"
                type="tel"
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                placeholder="+628..."
              />
            </div>

            

            
          </form>
        </div>

        <div class="lg:col-span-5">
          <div class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-100 shadow-xl shadow-gray-100/50 sticky top-28 overflow-hidden">
            <h2 class="text-xl font-bold text-gray-900 mb-6 truncate">Order Summary</h2>

            <div class="space-y-4 mb-6 max-h-80 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-200">
              <div
                v-for="item in items"
                :key="item.product.product_id"
                class="flex items-start justify-between gap-4 group"
              >
                <div class="flex items-center gap-4 min-w-0"> <div class="relative w-14 h-14 rounded-lg bg-gray-50 border border-gray-100 p-1 flex-shrink-0">
                    <img
                      :src="productImage(item.product)"
                      :alt="item.product.product_name"
                      class="w-full h-full object-contain mix-blend-multiply"
                    />
                  </div>
                  <div class="flex flex-col min-w-0">
                    <span class="text-sm font-medium text-gray-700 group-hover:text-black transition-colors line-clamp-2 break-words">
                      {{ item.product.product_name }}
                    </span>
                    <span class="text-xs text-gray-400">Qty: {{ item.quantity }}</span>
                  </div>
                </div>
                <span class="text-sm font-semibold text-gray-900 whitespace-nowrap">
                  {{ formatPrice(item.product.price * item.quantity) }}
                </span>
              </div>
            </div>

            <div class="space-y-3 pt-6 border-t border-gray-100 text-sm">
              <div class="flex justify-between items-center gap-2">
                <span class="text-gray-500 truncate">Subtotal</span>
                <span class="font-medium text-gray-900 whitespace-nowrap">{{ formatPrice(subtotal) }}</span>
              </div>
              <div class="flex justify-between items-center gap-2">
                <span class="text-gray-500 truncate">Shipping</span>
                <span class="font-medium text-green-600 whitespace-nowrap">Free</span>
              </div>
              <div class="border-t border-gray-100 my-2"></div>
              <div class="flex justify-between items-center gap-2">
                <span class="text-base font-bold text-gray-900 truncate">Total</span>
                <span class="text-xl font-extrabold text-gray-900 whitespace-nowrap">{{ formatPrice(total) }}</span>
              </div>
            </div>

            <div class="mt-8 space-y-4">
              <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider text-[11px] text-gray-400">Payment Method</h3>
              <label 
                class="flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-all gap-2"
                :class="billing.paymentMethod === 'qris' ? 'border-black bg-gray-50' : 'border-gray-200 hover:border-gray-300'"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="relative flex items-center flex-shrink-0">
                    <input
                      v-model="billing.paymentMethod"
                      type="radio"
                      value="qris"
                      class="peer h-4 w-4 cursor-pointer appearance-none rounded-full border border-gray-300 transition-all checked:border-black checked:bg-black"
                    />
                    <div class="pointer-events-none absolute left-1/2 top-1/2 h-2 w-2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white opacity-0 peer-checked:opacity-100"></div>
                  </div>
                  <span class="text-sm font-medium truncate">QRIS / E-Wallet</span>
                </div>
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/a2/Logo_QRIS.svg/1200px-Logo_QRIS.svg.png" class="h-4 w-auto object-contain flex-shrink-0 opacity-80" alt="QRIS" />
              </label>
            </div>

            <div class="mt-6">
              <div class="flex gap-2">
                <input
                  v-model="billing.coupon"
                  type="text"
                  placeholder="Coupon Code"
                  class="min-w-0 flex-1 bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                />
                <button
                  type="button"
                  @click="applyCoupon"
                  class="flex-shrink-0 px-5 py-3 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-black transition-colors"
                >
                  Apply
                </button>
              </div>
            </div>

            <button
              type="button"
              @click="placeOrder"
              :disabled="placing"
              class="w-full mt-6 bg-red-600 text-white text-sm font-bold py-4 rounded-xl hover:bg-red-700 disabled:opacity-70 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-2"
            >
              <span v-if="placing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span class="truncate">{{ placing ? 'Processing...' : 'Place Order' }}</span>
            </button>
            
          </div>
        </div>

      </section>
    </main>

    <Footer />
  </div>
</template>
