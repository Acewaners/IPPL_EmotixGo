<script setup>
import { computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { useCartStore } from '../stores/cart'
import { TrashIcon } from '@heroicons/vue/24/outline'

const cart = useCartStore()

const wishlist = computed(() => cart.wishlist)

// base url gambar dari Laravel
const STORAGE_BASE =
  import.meta.env.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const imageUrl = (p) => {
  if (!p || !p.image) return ''
  // kalau dari backend cuma kirim path relatif (products/xxx.jpg)
  // digabung sama STORAGE_BASE
  return p.image_full || `${STORAGE_BASE}/${p.image}`
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
  `Rp. ${Number(price || 0).toLocaleString('id-ID')}`
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 max-w-6xl mx-auto px-4 lg:px-0 py-10">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-lg md:text-xl font-semibold">
          Wishlist ({{ wishlist.length }})
        </h1>
        <button
          @click="moveAllToBag"
          class="px-4 py-2 border rounded-md text-sm hover:bg-gray-50"
        >
          Move All To Bag
        </button>
      </div>

      <!-- Empty state -->
      <div
        v-if="!wishlist.length"
        class="py-10 text-center text-gray-500 text-sm"
      >
        Wishlist masih kosong.
      </div>

      <!-- Grid produk -->
      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6"
      >
        <div
          v-for="w in wishlist"
          :key="w.product.product_id"
          class="bg-white border rounded-lg overflow-hidden hover:shadow-sm transition"
        >
          <!-- Bagian gambar -->
          <div
            class="relative bg-gray-100 h-56 flex items-center justify-center"
          >
            <img
              v-if="w.product.image"
              :src="imageUrl(w.product)"
              alt=""
              class="max-h-full object-contain"
            />

            <!-- Tombol hapus -->
            <button
              @click.stop="removeItem(w.product.product_id)"
              class="absolute top-2 right-2 w-7 h-7 rounded-full bg-white flex items-center justify-center shadow hover:bg-gray-100"
            >
              <TrashIcon class="w-4 h-4 text-gray-700" />
            </button>
          </div>

          <!-- Tombol Add to cart (merah) -->
          <button
            @click="addItemToCart(w.product)"
            class="w-full bg-red-500 text-white text-xs md:text-sm py-2 font-medium hover:bg-red-600"
          >
            Add To Cart
          </button>

          <!-- Nama & harga -->
          <div class="px-4 py-3">
            <p class="text-sm font-medium text-gray-900 truncate">
              {{ w.product.product_name }}
            </p>
            <p class="text-sm font-semibold text-red-500 mt-1">
              {{ formatPrice(w.product.price) }}
            </p>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
