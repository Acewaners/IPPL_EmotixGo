<script setup>
import { computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { useCartStore } from '../stores/cart'
import { useRouter, RouterLink } from 'vue-router'
import { XMarkIcon } from '@heroicons/vue/24/outline'

// base url gambar Laravel (wajib)
const STORAGE_BASE =
  import.meta.env.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const imageUrl = (p) => {
  if (!p?.image) return ''
  return p.image_full || `${STORAGE_BASE}/${p.image}`
}


const cart = useCartStore()
const r = useRouter()

const items = computed(() => cart.cartItems)
const subtotal = computed(() => cart.cartSubtotal)
const shipping = computed(() => 0) // contoh: gratis
const total = computed(() => subtotal.value + shipping.value)

const formatPrice = (price) =>
  `Rp. ${Number(price || 0).toLocaleString('id-ID')}`

const onQuantityChange = (pId, event) => {
  const value = Number(event.target.value)
  cart.updateQuantity(pId, value)
}

const removeItem = (pId) => {
  cart.removeFromCart(pId)
}

const proceedCheckout = () => {
  if (!items.value.length) return
  r.push('/checkout')
}

</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 max-w-6xl mx-auto px-4 lg:px-0 py-10 space-y-8">
      <!-- breadcrumb -->
      <section class="text-sm text-gray-500">
        <span>Home</span>
        <span class="mx-1">/</span>
        <span class="text-black font-medium">Cart</span>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Cart table -->
        <div class="lg:col-span-2 space-y-4">
          <div
            class="grid grid-cols-4 text-sm font-semibold border-b pb-3"
          >
            <span>Product</span>
            <span class="text-center">Price</span>
            <span class="text-center">Quantity</span>
            <span class="text-right">Subtotal</span>
          </div>

          <div
            v-if="!items.length"
            class="py-8 text-sm text-gray-500"
          >
            Cart masih kosong.
          </div>

          <div
            v-for="item in items"
            :key="item.product.product_id"
            class="grid grid-cols-4 items-center py-4 border-b text-sm"
          >
            <!-- Product -->
            <div class="flex items-center gap-3">
              <button
                @click="removeItem(item.product.product_id)"
                class="w-6 h-6 rounded-full border flex items-center justify-center text-gray-500 hover:bg-gray-100"
              >
                <XMarkIcon class="w-4 h-4" />
              </button>
<img
  v-if="item.product.image"
  :src="imageUrl(item.product)"
  class="w-16 h-16 rounded object-contain bg-gray-100"
/>

              <span class="truncate">
                {{ item.product.product_name }}
              </span>
            </div>

            <!-- Price -->
            <div class="text-center">
              {{ formatPrice(item.product.price) }}
            </div>

            <!-- Quantity -->
            <div class="flex justify-center">
              <select
                :value="item.quantity"
                @change="onQuantityChange(item.product.product_id, $event)"
                class="border rounded px-2 py-1 text-sm"
              >
                <option v-for="n in 10" :key="n" :value="n">
                  {{ n.toString().padStart(2, '0') }}
                </option>
              </select>
            </div>

            <!-- Subtotal -->
            <div class="text-right font-medium">
              {{
                formatPrice(
                  item.quantity * Number(item.product.price || 0)
                )
              }}
            </div>
          </div>

          <!-- Buttons bawah tabel -->
          <div class="flex justify-between pt-4">
            <RouterLink
              to="/"
              class="px-4 py-2 border rounded text-sm hover:bg-gray-50"
            >
              Return To Shop
            </RouterLink>

            <button
              class="px-4 py-2 border rounded text-sm hover:bg-gray-50"
              @click="() => items.forEach(i => cart.updateQuantity(i.product.product_id, i.quantity))"
            >
              Update Cart
            </button>
          </div>

          <!-- Coupon -->
          <div class="flex flex-wrap gap-3 pt-6 items-center">
            <input
              type="text"
              placeholder="Coupon Code"
              class="border rounded px-3 py-2 text-sm flex-1 min-w-[180px]"
            />
            <button
              class="bg-red-500 text-white px-5 py-2 rounded text-sm hover:bg-red-600"
            >
              Apply Coupon
            </button>
          </div>
        </div>

        <!-- Cart total -->
        <div class="border rounded-lg p-5 space-y-4">
          <h2 class="text-sm font-semibold mb-2">Cart Total</h2>

          <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
              <span>Subtotal:</span>
              <span>{{ formatPrice(subtotal) }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
              <span>Shipping:</span>
              <span>Free</span>
            </div>
            <div class="flex justify-between font-semibold">
              <span>Total:</span>
              <span>{{ formatPrice(total) }}</span>
            </div>
          </div>

          <button
            @click="proceedCheckout"
            class="w-full bg-red-500 text-white text-sm py-3 rounded mt-4 hover:bg-red-600"
          >
            Proceed to checkout
          </button>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
