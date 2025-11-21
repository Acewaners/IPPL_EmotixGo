<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'

import {
  HeartIcon,
  EyeIcon,
  ShoppingCartIcon,
} from '@heroicons/vue/24/outline'
import { StarIcon as StarSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const r = useRouter()
const cart = useCartStore()

// base url untuk storage laravel
const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const imageSrc = computed(() => {
  const p = props.product || {}

  if (p.image_full) return p.image_full
  if (p.image_url) return p.image_url
  if (typeof p.image === 'string') {
    if (p.image.startsWith('http')) return p.image
    // kalau backend kirim path relatif, sambung dengan STORAGE_BASE
    return `${STORAGE_BASE}/${p.image}`
  }

  // fallback
  return '/placeholder-product.png'
})

const isFav = computed(() =>
  cart.isInWishlist(props.product.product_id)
)

const rating = computed(() =>
  Number(props.product.rating || 0)
)
const reviewsCount = computed(() =>
  props.product.reviews_count ?? 0
)

const formatPrice = (price) =>
  `Rp. ${Number(price || 0).toLocaleString('id-ID')}`

const toggleWishlist = (e) => {
  e.stopPropagation()
  cart.toggleWishlist(props.product)
}

const goDetail = () => {
  // sesuaikan kalau route detail kamu beda
  r.push(`/products/${props.product.product_id}`)
}

const addToCart = (e) => {
  e.stopPropagation()
  cart.addToCart(props.product, 1)
}
</script>

<template>
  <div
    class="bg-white border rounded-xl overflow-hidden group cursor-pointer transition duration-200 hover:-translate-y-1 hover:shadow-[0_8px_24px_rgba(0,0,0,0.06)]"
    @click="goDetail"
  >
    <!-- Gambar + icon atas -->
    <div
      class="relative bg-gray-100 flex items-center justify-center h-56"
    >
      <img
        :src="imageSrc"
        :alt="product.product_name"
        class="max-h-full max-w-full object-contain"
      />

      <!-- wishlist -->
      <button
        @click.stop="toggleWishlist"
        class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm hover:bg-gray-100"
      >
        <HeartIcon
          class="w-4 h-4"
          :class="isFav ? 'text-red-500' : 'text-gray-700'"
        />
      </button>

      <!-- view / detail -->
      <button
        @click.stop="goDetail"
        class="absolute top-12 right-3 w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm hover:bg-gray-100"
      >
        <EyeIcon class="w-4 h-4 text-gray-700" />
      </button>
    </div>

    <!-- Konten -->
    <div class="px-4 pt-3 pb-4 space-y-1">
      <p class="text-xs text-gray-500 truncate">
        {{ product.category?.name || product.category_name || 'xxx' }}
      </p>

      <p class="text-sm font-medium truncate">
        {{ product.product_name }}
      </p>

      <div class="flex items-baseline gap-2">
        <span class="text-sm font-semibold text-red-500">
          {{ formatPrice(product.price) }}
        </span>

        <!-- harga coret opsional -->
        <span
          v-if="product.old_price"
          class="text-xs text-gray-400 line-through"
        >
          {{ formatPrice(product.old_price) }}
        </span>
      </div>

      <!-- rating -->
      <div class="flex items-center gap-1 text-[11px] mt-1">
        <StarSolid
          v-for="n in 5"
          :key="n"
          class="w-3.5 h-3.5"
          :class="
            n <= Math.round(rating)
              ? 'text-amber-400'
              : 'text-gray-300'
          "
        />
        <span class="ml-1 text-gray-500">
          ({{ reviewsCount }})
        </span>
      </div>
    </div>

    <!-- Bar Add To Cart (bawah) -->
    <button
      @click="addToCart"
      class="w-full bg-black text-white text-xs md:text-sm py-2 flex items-center justify-center gap-2 transition-colors group-hover:bg-gray-900"
    >
      <ShoppingCartIcon class="w-4 h-4" />
      <span>Add To Cart</span>
    </button>
  </div>
</template>
