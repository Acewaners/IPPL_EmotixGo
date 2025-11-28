<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink, useRouter } from 'vue-router'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { api } from '../lib/api'
import { useCartStore } from '../stores/cart'
import { HeartIcon, StarIcon } from '@heroicons/vue/24/solid'

const router = useRouter()
const route = useRoute()
const cart = useCartStore()

const loading = ref(true)
const error = ref('')
const product = ref(null)
const quantity = ref(1)

// 🔹 STATE REVIEW
const reviews = ref([])
const reviewMeta = ref({
  count: 0,
  avg_rating: 0,
  distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
  sentiment: { positive: 0, neutral: 0, negative: 0 },
})
const reviewsLoading = ref(false)
const reviewsError = ref('')

// base url gambar
const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const fullImage = computed(() => {
  if (!product.value) return ''
  const p = product.value
  if (p.image_full) return p.image_full
  if (p.image_url) return p.image_url
  if (!p.image) return ''
  if (p.image.startsWith('http')) return p.image
  return `${STORAGE_BASE}/${p.image}`
})

const galleryImages = computed(() => {
  if (!fullImage.value) return []
  return [fullImage.value, fullImage.value, fullImage.value, fullImage.value]
})

const selectedImage = ref('')

// 🔹 ambil review per produk
const loadReviews = async (productId) => {
  reviewsLoading.value = true
  reviewsError.value = ''
  try {
    // sesuaikan dengan route backend kamu
    const res = await api.get(`/products/${productId}/reviews`)
    const payload = res.data || {}

    // kalau kamu balikin { data: [...], meta: {...} }
    reviews.value = payload.data || []
    reviewMeta.value = {
      ...reviewMeta.value,
      ...(payload.meta || {}),
    }
  } catch (e) {
    console.error(e)
    reviewsError.value =
      e?.response?.data?.message || 'Gagal memuat review.'
  } finally {
    reviewsLoading.value = false
  }
}

onMounted(async () => {
  loading.value = true
  error.value = ''
  selectedImage.value = ''

  try {
    const id = route.params.id
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

      // setelah produk ketemu, baru load review
      await loadReviews(found.product_id)
    }
  } catch (e) {
    console.error(e)
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
}

// Buy Now: tambah ke cart lalu ke halaman cart
const buyNow = () => {
  if (!product.value) return
  if (Number(product.value.stock || 0) <= 0) return

  cart.addToCart(product.value, quantity.value)
  router.push('/cart')
}

const formatPrice = (price) =>
  `Rp. ${Number(price || 0).toLocaleString('id-ID')}`

const inStock = computed(
  () => Number(product.value?.stock || 0) > 0
)

// 🔹 computed rating & sentiment (untuk tampilan)
const avgRating = computed(() =>
  reviewMeta.value && typeof reviewMeta.value.avg_rating === 'number'
    ? reviewMeta.value.avg_rating
    : 0
)

const reviewCount = computed(() =>
  reviewMeta.value && typeof reviewMeta.value.count === 'number'
    ? reviewMeta.value.count
    : 0
)

const sentimentPercent = computed(() => {
  const s = reviewMeta.value.sentiment || {}
  const positive = s.positive || 0
  const neutral = s.neutral || 0
  const negative = s.negative || 0
  const total = positive + neutral + negative || 1

  return {
    positive: Math.round((positive / total) * 100),
    neutral: Math.round((neutral / total) * 100),
    negative: Math.round((negative / total) * 100),
  }
})
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

        <!-- content utama (gambar + info kanan) -->
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

              <!-- ⭐ rating & stock (diganti pakai data review) -->
              <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                <div class="flex items-center gap-1">
                  <StarIcon class="w-4 h-4 text-yellow-400" />
                  <span>{{ avgRating.toFixed(1) }}</span>
                  <span class="mx-1">|</span>
                  <span>{{ reviewCount }} Reviews</span>
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
                @click="buyNow"
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

            <!-- delivery info (masih sama) -->
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

        <!-- 👇 Deskripsi kiri + panel review kanan + list review -->
        <section
          v-if="product && !loading && !error"
          class="mt-10 text-sm leading-relaxed"
        >
          <div class="grid md:grid-cols-3 gap-8">
            <!-- Kiri: Deskripsi -->
            <div class="md:col-span-2 space-y-3">
              <h2 class="text-base font-semibold">
                {{ product.product_name }} – Deskripsi Produk
              </h2>
              <p class="text-gray-700 whitespace-pre-line">
                {{ product.description || 'Belum ada deskripsi untuk produk ini.' }}
              </p>
            </div>

            <!-- Kanan: Summary Rating + Sentiment -->
            <aside class="border rounded-xl p-4 bg-gray-50 space-y-4 text-xs">
              <h3 class="text-sm font-semibold mb-1">
                Customer Reviews & Sentiment
              </h3>

              <!-- Overall Rating -->
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-2xl font-semibold flex items-center gap-1">
                    <StarIcon class="w-5 h-5 text-yellow-400" />
                    <span>{{ avgRating.toFixed(1) }}</span>
                    <span class="text-xs text-gray-400">/ 5</span>
                  </p>
                  <p class="text-[11px] text-gray-500">
                    {{ reviewCount }} total reviews
                  </p>
                </div>
              </div>

              <!-- Distribution 5–1 -->
              <div class="space-y-1">
                <div
                  v-for="star in [5,4,3,2,1]"
                  :key="star"
                  class="flex items-center gap-2"
                >
                  <span class="w-6 text-right text-[11px] text-gray-500">
                    {{ star }}★
                  </span>
                  <div class="flex-1 h-2 bg-white rounded-full overflow-hidden border border-gray-200">
                    <div
                      class="h-full bg-yellow-400"
                      :style="{
                        width:
                          (reviewMeta.distribution?.[star] || 0) && reviewCount
                            ? (reviewMeta.distribution[star] / reviewCount * 100) + '%'
                            : '0%'
                      }"
                    />
                  </div>
                  <span class="w-6 text-[11px] text-gray-500 text-right">
                    {{ reviewMeta.distribution?.[star] || 0 }}
                  </span>
                </div>
              </div>

              <!-- Sentiment (versi sederhana dulu) -->
              <div class="border-t pt-3 space-y-2">
                <p class="text-[11px] font-semibold text-gray-700">
                  Sentiment (berdasarkan rating)
                </p>
                <div class="space-y-1">
                  <div class="flex justify-between text-[11px]">
                    <span class="text-emerald-600">Positive (4–5★)</span>
                    <span>
                      {{ reviewMeta.sentiment.positive || 0 }}
                      ({{ sentimentPercent.positive }}%)
                    </span>
                  </div>
                  <div class="flex justify-between text-[11px]">
                    <span class="text-yellow-600">Neutral (3★)</span>
                    <span>
                      {{ reviewMeta.sentiment.neutral || 0 }}
                      ({{ sentimentPercent.neutral }}%)
                    </span>
                  </div>
                  <div class="flex justify-between text-[11px]">
                    <span class="text-red-600">Negative (1–2★)</span>
                    <span>
                      {{ reviewMeta.sentiment.negative || 0 }}
                      ({{ sentimentPercent.negative }}%)
                    </span>
                  </div>
                </div>

                <p class="text-[10px] text-gray-400 mt-1">
                  *Untuk sementara, sentiment dihitung dari rating.
                </p>
              </div>
            </aside>
          </div>

          <!-- List review di bawah -->
          <div class="mt-10 space-y-4">
            <h3 class="text-sm font-semibold">
              Customer Reviews
            </h3>

            <div v-if="reviewsLoading" class="text-xs text-gray-500">
              Loading reviews...
            </div>
            <div v-else-if="reviewsError" class="text-xs text-red-500">
              {{ reviewsError }}
            </div>
            <div v-else-if="!reviews.length" class="text-xs text-gray-500">
              Belum ada review untuk produk ini. Jadilah yang pertama, sayang!
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="rev in reviews"
                :key="rev.review_id"
                class="border rounded-lg px-4 py-3 text-xs md:text-sm bg-white"
              >
                <div class="flex justify-between items-start gap-2">
                  <div>
                    <p class="font-semibold text-gray-800">
                      {{ rev.buyer?.name || 'Verified Buyer' }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                      {{ new Date(rev.created_at).toLocaleDateString('id-ID') }}
                    </p>
                  </div>
                  <div class="flex items-center gap-1 text-yellow-400 text-[11px]">
                    <StarIcon
                      v-for="n in rev.rating"
                      :key="n"
                      class="w-3 h-3"
                    />
                    <span class="text-gray-600 ml-1">
                      {{ rev.rating }}★
                    </span>
                  </div>
                </div>

                <p class="mt-2 text-gray-700 whitespace-pre-line">
                  {{ rev.review_text }}
                </p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>

    <Footer />
  </div>
</template>

