<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router' 
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { useCartStore } from '../stores/cart'
import { TrashIcon, ShoppingBagIcon, HeartIcon } from '@heroicons/vue/24/outline'

const cart = useCartStore()
const router = useRouter()

const wishlist = computed(() => cart.wishlist)

// Helper URL Gambar
const STORAGE_BASE = import.meta.env.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const imageUrl = (p) => {
  if (!p || !p.image) return '/placeholder-product.png' 
  if (p.image_full) return p.image_full
  if (p.image.startsWith('http')) return p.image
  return `${STORAGE_BASE}/${p.image}`
}

const moveAllToBag = () => {
  if (!wishlist.value.length) return
  cart.moveWishlistToCart()
}

const removeItem = (productId) => {
  cart.removeFromWishlist(productId)
}

const addItemToCart = (product) => {
  cart.addToCart(product, 1)
  cart.removeFromWishlist(product.product_id)
}

const formatPrice = (price) =>
  `Rp ${Number(price || 0).toLocaleString('id-ID')}`
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
      
      <div class="flex flex-col md:flex-row items-center justify-between mb-10 gap-4">
        <div class="flex items-center gap-3">
          <h1 class="text-3xl font-bold tracking-tight">My Wishlist</h1>
          <span v-if="wishlist.length" class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">
            {{ wishlist.length }} items
          </span>
        </div>

        <button
          v-if="wishlist.length"
          @click="moveAllToBag"
          class="group flex items-center gap-2 px-6 py-3 bg-white border border-gray-200 text-sm font-medium rounded-full hover:bg-black hover:text-white hover:border-black transition-all shadow-sm active:scale-95"
        >
          <ShoppingBagIcon class="w-4 h-4" />
          <span>Move All To Bag</span>
        </button>
      </div>

      <div
        v-if="!wishlist.length"
        class="flex flex-col items-center justify-center py-24 text-center border-2 border-dashed border-gray-100 rounded-3xl bg-gray-50/50"
      >
        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
          <HeartIcon class="w-10 h-10 text-gray-300" />
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Your wishlist is empty</h3>
        <p class="text-gray-500 text-sm mb-6 max-w-xs">
          Looks like you haven't added anything to your wishlist yet.
        </p>
        <button 
          @click="router.push('/products')"
          class="px-8 py-3 bg-black text-white rounded-full text-sm font-medium hover:bg-gray-800 transition-transform active:scale-95 shadow-lg"
        >
          Start Shopping
        </button>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
        
        <div
          v-for="w in wishlist"
          :key="w.product.product_id"
          class="group flex flex-col bg-white border border-gray-100 rounded-2xl overflow-hidden hover:shadow-xl hover:border-gray-200 transition-all duration-300 relative"
        >
          <div class="relative aspect-square bg-gray-50 p-6 flex items-center justify-center overflow-hidden">
            <img
              :src="imageUrl(w.product)"
              :alt="w.product.product_name"
              class="w-full h-full object-contain mix-blend-multiply transition-transform duration-500 group-hover:scale-110"
            />

            <button
              @click.stop="removeItem(w.product.product_id)"
              class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-sm text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors z-10"
              title="Remove item"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="flex-1 p-5 flex flex-col">
            <div class="mb-3">
              <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-1">
                {{ w.product.category?.name || 'Product' }}
              </p>
              <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 leading-relaxed h-[2.5rem]">
                {{ w.product.product_name }}
              </h3>
            </div>

            <div class="mt-auto mb-4">
              <p class="text-lg font-bold text-gray-900">
                {{ formatPrice(w.product.price) }}
              </p>
            </div>

            <button
              @click="addItemToCart(w.product)"
              class="w-full py-2.5 rounded-xl bg-black text-white text-sm font-medium flex items-center justify-center gap-2 hover:bg-gray-800 transition-colors active:scale-95 shadow-sm"
            >
              <ShoppingBagIcon class="w-4 h-4" />
              <span>Add to Cart</span>
            </button>
          </div>
        </div>
        </div>
    </main>

    <Footer />
  </div>
</template>