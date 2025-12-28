<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'

import {
  HeartIcon,
  EyeIcon,
  ShoppingBagIcon, // Saya ganti ShoppingCart jadi Bag biar lebih modern
} from '@heroicons/vue/24/outline'
import { HeartIcon as HeartSolid, StarIcon as StarSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const r = useRouter()
const cart = useCartStore()

const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const imageSrc = computed(() => {
  const p = props.product || {}
  if (p.image_full) return p.image_full
  if (p.image_url) return p.image_url
  if (typeof p.image === 'string') {
    if (p.image.startsWith('http')) return p.image
    return `${STORAGE_BASE}/${p.image}`
  }
  return '/placeholder-product.png'
})

const isFav = computed(() =>
  cart.isInWishlist(props.product.product_id)
)

const rating = computed(() => Number(props.product.rating || 0))
const reviewsCount = computed(() => props.product.reviews_count ?? 0)

const formatPrice = (price) =>
  `Rp ${Number(price || 0).toLocaleString('id-ID')}`

const toggleWishlist = (e) => {
  e.stopPropagation()
  cart.toggleWishlist(props.product)
}

const goDetail = () => {
  r.push(`/products/${props.product.product_id}`)
}

const addToCart = (e) => {
  e.stopPropagation()
  cart.addToCart(props.product, 1)
}
</script>

<template>
  <div
    class="group relative bg-white rounded-2xl cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-1 border border-transparent hover:border-gray-100"
    @click="goDetail"
  >
    <div class="relative aspect-square bg-gray-50 rounded-2xl overflow-hidden p-6 flex items-center justify-center">
      
      <img
        :src="imageSrc"
        :alt="product.product_name"
        class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110 mix-blend-multiply"
      />

      <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
        <button
          @click.stop="toggleWishlist"
          class="w-9 h-9 rounded-full bg-white text-gray-700 flex items-center justify-center shadow-md hover:bg-black hover:text-white transition-colors"
          title="Add to Wishlist"
        >
          <component 
            :is="isFav ? HeartSolid : HeartIcon" 
            class="w-5 h-5" 
            :class="isFav ? 'text-red-500 hover:text-white' : ''"
          />
        </button>
        
        <button
          @click.stop="goDetail"
          class="w-9 h-9 rounded-full bg-white text-gray-700 flex items-center justify-center shadow-md hover:bg-black hover:text-white transition-colors"
          title="Quick View"
        >
          <EyeIcon class="w-5 h-5" />
        </button>
      </div>

      <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out">
        <button
          @click="addToCart"
          class="w-full bg-black/90 backdrop-blur-sm text-white py-3 rounded-xl font-medium flex items-center justify-center gap-2 hover:bg-black shadow-lg"
        >
          <ShoppingBagIcon class="w-5 h-5" />
          <span>Add to Cart</span>
        </button>
      </div>
    </div>

    <div class="mt-4 px-1 space-y-1">
      <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">
        {{ product.category?.name || 'Electronics' }}
      </p>

      <div class="flex justify-between items-start gap-2">
        <h3 class="text-sm font-medium text-gray-900 line-clamp-2 leading-snug group-hover:text-red-500 transition-colors">
          {{ product.product_name }}
        </h3>
        
        <div class="text-right shrink-0">
          <p class="text-sm font-bold text-gray-900">
            {{ formatPrice(product.price) }}
          </p>
          <p v-if="product.old_price" class="text-[10px] text-gray-400 line-through">
            {{ formatPrice(product.old_price) }}
          </p>
        </div>
      </div>

      <div class="flex items-center pt-1">
        <StarSolid class="w-3.5 h-3.5 text-yellow-400" />
        <span class="ml-1 text-xs font-medium text-gray-700">{{ rating }}</span>
        <span class="mx-1 text-gray-300">•</span>
        <span class="text-xs text-gray-400">{{ reviewsCount }} reviews</span>
      </div>
    </div>
  </div>
</template>