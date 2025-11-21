<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { api } from '../lib/api'
import { useCartStore } from '../stores/cart'
import { HeartIcon, StarIcon } from '@heroicons/vue/24/solid'

const route = useRoute()
const cart = useCartStore()

const loading = ref(true)
const error = ref('')
const product = ref(null)
const quantity = ref(1)

// base url gambar (sama pola dengan halaman seller)
const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const fullImage = computed(() => {
  if (!product.value) return ''
  const p = product.value
  if (p.image_full) return p.image_full
  if (p.image_url) return p.image_url
  if (!p.image) return ''
  // kalau backend cuma kirim path relatif
  if (p.image.startsWith('http')) return p.image
  return `${STORAGE_BASE}/${p.image}`
})

// untuk gallery sederhana (sementara 1 gambar diulang)
const galleryImages = computed(() => {
  if (!fullImage.value) return []
  return [fullImage.value, fullImage.value, fullImage.value, fullImage.value]
})

const selectedImage = ref('')

onMounted(async () => {
  loading.value = true
  error.value = ''
  selectedImage.value = ''

  try {
    const id = route.params.id

    // backend belum punya /products/{id}, jadi ambil semua dulu lalu filter
    const res = await api.get('/products')
    const list = res.data?.data ?? []

    const found = list.find(
      (p) => String(p.product_id) === String(id)
    )

    if (!found) {
      error.value = 'Produk tidak ditemukan.'
    } else {
      product.value = found
      selectedImage.value = fullImage.value
    }
  } catch (e) {
    error.value =
      e?.response?.data?.message || 'Gagal memuat detail produk.'
  } finally {
    loading.value = false
  }
})

const isFavorite = computed(() =>
  product.value
    ? cart.isInWishlist(product.value.product_id)
    : false
)

const toggleWishlist = () => {
  if (!product.value) return
  cart.toggleWishlist(product.value)
}

const increaseQty = () => {
  if (!product.value) return
  const max = Number(product.value.stock ?? 99)
  if (quantity.value < max) quantity.value++
}

const decreaseQty = () => {
  if (quantity.value > 1) quantity.value--
}

const handleQtyInput = (e) => {
  let v = Number(e.target.value || 1)
  if (Number.isNaN(v) || v < 1) v = 1
  const max = Number(product.value?.stock ?? 99)
  if (v > max) v = max
  quantity.value = v
}

const addToCart = () => {
  if (!product.value) return
  if (Number(product.value.stock || 0) <= 0) return
  cart.addToCart(product.value, quantity.value)
  // nanti bisa diarahkan ke cart / toast
  alert('Produk ditambahkan ke cart.')
}

const formatPrice = (price) =>
  `Rp. ${Number(price || 0).toLocaleString('id-ID')}`

const inStock = computed(
  () => Number(product.value?.stock || 0) > 0
)
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1">
      <div class="max-w-6xl mx-auto px-4 lg:px-0 py-10 space-y-8">
        <!-- breadcrumb -->
        <nav class="text-xs md:text-sm text-gray-500">
          <RouterLink to="/" class="hover:text-black">Home</RouterLink>
          <span class="mx-1">/</span>
          <span>Gaming</span>
          <span class="mx-1">/</span>
          <span class="text-black font-medium">
            {{ product?.product_name || 'Product' }}
          </span>
        </nav>

        <!-- loading / error -->
        <div v-if="loading" class="py-16 text-center text-gray-500">
          Loading detail produk...
        </div>
        <div v-else-if="error" class="py-16 text-center text-red-500">
          {{ error }}
        </div>

        <!-- content -->
        <div
          v-else-if="product"
          class="grid grid-cols-1 md:grid-cols-12 gap-10 items-start"
        >
          <!-- left thumbnails -->
          <div class="md:col-span-2 flex md:flex-col gap-3 order-2 md:order-1">
            <button
              v-for="(img, idx) in galleryImages"
              :key="idx"
              type="button"
              class="w-20 h-20 border rounded-lg overflow-hidden flex items-center justify-center bg-gray-50 hover:border-black transition"
              @click="selectedImage = img"
            >
              <img
                :src="img"
                alt=""
                class="w-full h-full object-cover"
              />
            </button>
          </div>

          <!-- main image -->
          <div
            class="md:col-span-5 order-1 md:order-2 border rounded-xl bg-gray-50 flex items-center justify-center px-6 py-8"
          >
            <img
              :src="selectedImage || fullImage"
              alt=""
              class="w-full max-h-[360px] object-contain"
            />
          </div>

          <!-- right info -->
          <div class="md:col-span-5 order-3 space-y-5">
            <div>
              <h1 class="text-lg md:text-xl font-semibold">
                {{ product.product_name }}
              </h1>

              <!-- rating & stock -->
              <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                <div class="flex items-center gap-1">
                  <StarIcon class="w-4 h-4 text-yellow-400" />
                  <span>4.8</span>
                  <span class="mx-1">|</span>
                  <span>150 Reviews</span>
                </div>
                <span class="text-gray-300">|</span>
                <span
                  :class="[
                    'text-xs',
                    inStock ? 'text-emerald-500' : 'text-red-500'
                  ]"
                >
                  {{ inStock ? 'In Stock' : 'Out of Stock' }}
                </span>
              </div>
            </div>

            <!-- price -->
            <div class="space-y-1">
              <p class="text-2xl font-semibold text-red-500">
                {{ formatPrice(product.price) }}
              </p>
              <p class="text-xs text-gray-400">
                Stok: {{ product.stock ?? 0 }}
              </p>
            </div>

            <!-- (optional) colours / size dummy -->
            <div class="space-y-4 text-sm">
              <!-- <div class="flex items-center gap-4">
                <span class="w-16 text-gray-500">Colours:</span>
                <div class="flex gap-2">
                  <button
                    class="w-5 h-5 rounded-full border-2 border-black bg-black"
                  ></button>
                  <button
                    class="w-5 h-5 rounded-full border bg-gray-400"
                  ></button>
                </div>
              </div> -->

              <!-- <div class="flex items-center gap-4">
                <span class="w-16 text-gray-500">Size:</span>
                <div class="flex gap-2">
                  <button
                    class="px-3 py-1 border rounded text-xs hover:border-black"
                  >
                    S
                  </button>
                  <button
                    class="px-3 py-1 border rounded text-xs hover:border-black"
                  >
                    M
                  </button>
                  <button
                    class="px-3 py-1 border rounded text-xs hover:border-black"
                  >
                    L
                  </button>
                </div>
              </div> -->
            </div>

            <!-- qty + buy now + wishlist -->
            <div class="flex items-center gap-4">
              <!-- qty -->
              <div class="flex items-center border rounded-md overflow-hidden">
                <button
                  type="button"
                  class="px-3 py-2 text-sm border-r hover:bg-gray-50"
                  @click="decreaseQty"
                >
                  -
                </button>
                <input
                  type="number"
                  min="1"
                  class="w-12 text-center text-sm focus:outline-none"
                  :value="quantity"
                  @input="handleQtyInput"
                />
                <button
                  type="button"
                  class="px-3 py-2 text-sm border-l hover:bg-gray-50"
                  @click="increaseQty"
                >
                  +
                </button>
              </div>

              <!-- buy now -->
              <button
                type="button"
                class="flex-1 bg-red-500 text-white text-sm font-medium py-3 rounded-md hover:bg-red-600 disabled:opacity-60 disabled:cursor-not-allowed"
                :disabled="!inStock"
                @click="addToCart"
              >
                Buy Now
              </button>

              <!-- heart -->
              <button
                type="button"
                @click="toggleWishlist"
                class="w-10 h-10 rounded-md border flex items-center justify-center hover:bg-gray-50"
              >
                <HeartIcon
                  class="w-5 h-5"
                  :class="isFavorite ? 'text-red-500' : 'text-gray-500'"
                />
              </button>
            </div>

            <!-- delivery info -->
            <div class="mt-4 border rounded-lg divide-y text-xs">
              <div class="flex items-start gap-3 px-4 py-3">
                <div class="mt-1 text-xl">🚚</div>
                <div>
                  <p class="font-semibold text-gray-800">
                    Free Delivery
                  </p>
                  <p class="text-gray-500">
                    Enter your postal code for delivery availability.
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3 px-4 py-3">
                <div class="mt-1 text-xl">↩️</div>
                <div>
                  <p class="font-semibold text-gray-800">
                    Return Delivery
                  </p>
                  <p class="text-gray-500">
                    Free 30 Days Delivery Returns. Details.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- description -->
        <section v-if="product && !loading && !error" class="mt-10 text-sm leading-relaxed">
          <h2 class="text-base font-semibold mb-3">
            {{ product.product_name }} – Deskripsi Produk
          </h2>
          <p class="text-gray-700 whitespace-pre-line">
            {{ product.description || 'Belum ada deskripsi untuk produk ini.' }}
          </p>
        </section>
      </div>
    </main>

    <Footer />
  </div>
</template>
