<script setup>
import { computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { useCartStore } from '../stores/cart'
import { useRouter, RouterLink } from 'vue-router'
import { XMarkIcon, TrashIcon, ArrowRightIcon } from '@heroicons/vue/24/outline'

// base url gambar Laravel (wajib)
const STORAGE_BASE = import.meta.env.VITE_STORAGE_BASE || 'http://localhost:8000/storage'

const imageUrl = (product) => {
  if (!product || !product.image) return '/dummy-qr.png' // Gambar default jika kosong
  
  // Jika sudah berupa link lengkap (Cloudinary), langsung gunakan
  if (product.image.startsWith('http')) {
    return product.image
  }
  
  // Jika path lokal, tambahkan prefix storage
  return `${STORAGE_BASE}/${product.image}`
}


const cart = useCartStore()
const r = useRouter()

const items = computed(() => cart.cartItems)
const subtotal = computed(() => cart.cartSubtotal)
const shipping = computed(() => 0) // contoh: gratis
const total = computed(() => subtotal.value + shipping.value)

const formatPrice = (price) =>
  `Rp ${Number(price || 0).toLocaleString('id-ID')}`

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
  <div class="min-h-screen flex flex-col bg-white font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
      
      <div class="mb-10">
        <nav class="flex items-center text-sm text-gray-500 mb-2">
           <RouterLink to="/" class="hover:text-black transition-colors">Home</RouterLink>
           <span class="mx-2 text-gray-300">/</span>
           <span class="text-black font-medium">Shopping Cart</span>
        </nav>
        <h1 class="text-3xl font-bold tracking-tight">Your Cart</h1>
      </div>

      <section class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12 items-start">
        
        <div class="lg:col-span-2">
          
          <div class="hidden md:grid grid-cols-12 gap-4 border-b border-gray-100 pb-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
            <div class="col-span-6">Product</div>
            <div class="col-span-2 text-center">Price</div>
            <div class="col-span-2 text-center">Quantity</div>
            <div class="col-span-2 text-right">Subtotal</div>
          </div>

          <div v-if="!items.length" class="py-16 flex flex-col items-center justify-center text-center bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-100 mt-4">
             <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4 text-gray-300">
                <XMarkIcon class="w-8 h-8" />
             </div>
             <h3 class="text-lg font-bold text-gray-900">Cart is empty</h3>
             <p class="text-gray-500 mb-6 text-sm">Looks like you haven't added any items yet.</p>
             <RouterLink to="/products" class="px-8 py-3 bg-black text-white rounded-full text-sm font-medium hover:bg-gray-800 transition-colors shadow-lg">
                Start Shopping
             </RouterLink>
          </div>

          <div v-else class="divide-y divide-gray-50">
            <div
              v-for="item in items"
              :key="item.product.product_id"
              class="py-6 grid grid-cols-1 md:grid-cols-12 gap-4 items-center group"
            >
              <div class="col-span-1 md:col-span-6 flex items-center gap-5">
                <div class="w-20 h-20 shrink-0 bg-gray-50 rounded-xl p-2 flex items-center justify-center border border-gray-100 overflow-hidden">
                    <img
                      v-if="item.product.image"
                      :src="imageUrl(item.product)"
                      :alt="item.product.product_name"
                      class="w-full h-full object-contain mix-blend-multiply"
                    />
                </div>
                
                <div class="min-w-0">
                  <h3 class="text-sm font-semibold text-gray-900 truncate pr-4">{{ item.product.product_name }}</h3>
                  <p class="text-xs text-gray-500 mt-1 mb-2">{{ item.product.category?.name || 'Category' }}</p>
                  
                  <div class="md:hidden flex items-center gap-3">
                      <span class="text-sm font-bold">{{ formatPrice(item.product.price) }}</span>
                      <button @click="removeItem(item.product.product_id)" class="text-xs text-red-500 underline decoration-red-200">Remove</button>
                   </div>
                </div>
              </div>

              <div class="hidden md:block col-span-2 text-center text-sm font-medium text-gray-600">
                {{ formatPrice(item.product.price) }}
              </div>

              <div class="col-span-1 md:col-span-2 flex justify-start md:justify-center">
                 <div class="relative">
                    <select
                      :value="item.quantity"
                      @change="onQuantityChange(item.product.product_id, $event)"
                      class="appearance-none bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-16 p-2.5 text-center cursor-pointer font-bold transition-colors"
                    >
                      <option v-for="n in 10" :key="n" :value="n">
                        {{ n }}
                      </option>
                    </select>
                 </div>
              </div>

              <div class="hidden md:flex col-span-2 items-center justify-end gap-6">
                <span class="font-bold text-sm text-gray-900">{{ formatPrice(item.quantity * Number(item.product.price || 0)) }}</span>
                <button
                  @click="removeItem(item.product.product_id)"
                  class="p-2 rounded-full text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                  title="Remove item"
                >
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>

          <div v-if="items.length" class="flex flex-col md:flex-row justify-between items-center gap-6 mt-10 pt-8 border-t border-gray-100">
             <RouterLink
              to="/"
              class="text-sm font-medium text-gray-500 hover:text-black transition-colors flex items-center gap-2"
            >
              <span>&larr;</span> Continue Shopping
            </RouterLink>

            <div class="flex w-full md:w-auto items-center gap-2">
                <input
                  type="text"
                  placeholder="Promo Code"
                  class="flex-1 md:w-64 px-5 py-3 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all"
                />
                <button class="px-6 py-3 bg-gray-900 text-white rounded-full text-sm font-medium hover:bg-black transition-colors shadow-md">
                   Apply
                </button>
            </div>
          </div>
        </div>

        <div v-if="items.length" class="lg:col-span-1">
          <div class="bg-gray-50 rounded-3xl p-6 lg:p-8 border border-gray-100 sticky top-28">
             <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>
             
             <div class="space-y-4 text-sm mb-8">
                <div class="flex justify-between text-gray-600">
                   <span>Subtotal</span>
                   <span class="font-bold text-gray-900">{{ formatPrice(subtotal) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                   <span>Shipping</span>
                   <span class="font-medium text-green-600">Free</span>
                </div>
                
                <div class="border-t border-gray-200 my-2 pt-4 flex justify-between items-center">
                   <span class="font-bold text-base text-gray-900">Total</span>
                   <span class="font-extrabold text-2xl text-gray-900">{{ formatPrice(total) }}</span>
                </div>
             </div>

             <button
               @click="proceedCheckout"
               class="w-full bg-black text-white py-4 rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-gray-800 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300"
             >
                Proceed to Checkout
                <ArrowRightIcon class="w-4 h-4" />
             </button>
             
             <p class="text-center text-xs text-gray-400 mt-4">
                Secure Checkout - SSL Encrypted
             </p>
          </div>
        </div>

      </section>
    </main>

    <Footer />
  </div>
</template>