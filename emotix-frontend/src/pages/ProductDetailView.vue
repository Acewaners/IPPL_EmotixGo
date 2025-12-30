<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink, useRouter } from 'vue-router'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { api } from '../lib/api'
import { useCartStore } from '../stores/cart'
import { HeartIcon, StarIcon, TrashIcon } from '@heroicons/vue/24/solid'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid'
import { useAuth } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const cart = useCartStore()
const auth = useAuth()

const loading = ref(true)
const error = ref('')
const product = ref(null)
const quantity = ref(1)

const reviews = ref([])
const reviewMeta = ref({
  count: 0,
  avg_rating: 0,
  distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
  sentiment: { positive: 0, neutral: 0, negative: 0 },
})
const reviewsLoading = ref(false)
const reviewsError = ref('')

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

const deleteReview = async (rev) => {
  if (!confirm('Are you sure you want to delete this review?')) return

  try {
    await api.delete(`/reviews/${rev.review_id}`)
    
    await loadReviews(product.value.product_id)
    
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to delete review')
  }
}

const getSentimentBadge = (sentiment) => {
  switch (sentiment) {
    case 'positive':
      return { 
        label: 'Positive 😊', 
        classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' 
      }
    case 'negative':
      return { 
        label: 'Negative 😟', 
        classes: 'bg-rose-50 text-rose-700 border-rose-200' 
      }
    case 'neutral':
      return { 
        label: 'Neutral 😐', 
        classes: 'bg-amber-50 text-amber-700 border-amber-200' 
      }
    default:
      return null
  }
}

const loadReviews = async (productId) => {
  reviewsLoading.value = true
  reviewsError.value = ''
  try {
    const res = await api.get(`/products/${productId}/reviews?t=${new Date().getTime()}`)
    const payload = res.data || {}

    reviews.value = payload.data || []
    reviewMeta.value = {
      ...reviewMeta.value,
      ...(payload.meta || {}),
    }
  } catch (e) {
    console.error(e)
    reviewsError.value =
      e?.response?.data?.message || 'Failed to load reviews.'
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
      error.value = 'Product not found.'
    } else {
      product.value = found
      selectedImage.value = fullImage.value

      await loadReviews(found.product_id)
    }
  } catch (e) {
    console.error(e)
    error.value =
      e?.response?.data?.message || 'Failed to load product details.'
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

const newReview = ref({
  text: '',
  rating: null 
})
const isSubmitting = ref(false)
const submitError = ref('')

// --- 2. Fungsi Submit Review ---
const submitReview = async () => {
  const text = newReview.value.text ? newReview.value.text.trim() : '';
  const rating = newReview.value.rating;

  if (!text && !rating) {
    submitError.value = 'Please provide a Star OR write a Review.';
    return;
  }

  if (text && text.length < 5) {
    submitError.value = 'Review text must be at least 5 characters long.';
    return;
  }

  isSubmitting.value = true
  submitError.value = ''

  try {
    const res = await api.post('/reviews', {
      product_id: product.value.product_id,
      review_text: text || null, 
      rating: rating || null     
    })

    console.log("DEBUG RES BACKEND:", res.data);

    const newReviewData = res.data.data || res.data; 

    if (newReviewData) {
        reviews.value.unshift(newReviewData);
        
        if (reviewMeta.value) reviewMeta.value.count++;
    }
    newReview.value.text = ''
    newReview.value.rating = null
    
    setTimeout(() => {
        loadReviews(product.value.product_id)
    }, 1000)

    await loadReviews(product.value.product_id)
    
  } catch (e) {
    submitError.value = e.response?.data?.message || 'Failed to submit review.'
  } finally {
    isSubmitting.value = false
  }
}

</script>

<template>
  <div class="min-h-screen flex flex-col bg-white font-sans text-gray-900">
    <Navbar />

    <main class="flex-1">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        
        <nav class="flex items-center text-sm text-gray-500 mb-8 overflow-x-auto whitespace-nowrap pb-2">
          <RouterLink to="/" class="hover:text-black transition-colors">Home</RouterLink>
          <span class="mx-2 text-gray-300">/</span>
          <span class="hover:text-black transition-colors cursor-pointer">Products</span>
          <span class="mx-2 text-gray-300">/</span>
          <span class="text-black font-medium truncate">{{ product?.product_name || 'Loading...' }}</span>
        </nav>

        <div v-if="loading" class="flex flex-col items-center justify-center py-32 space-y-4">
          <div class="w-10 h-10 border-4 border-gray-200 border-t-black rounded-full animate-spin"></div>
          <p class="text-gray-500 text-sm">Loading product details...</p>
        </div>

        <div v-else-if="error" class="py-32 text-center">
          <div class="inline-flex bg-red-50 p-4 rounded-full mb-4">
            <XCircleIcon class="w-8 h-8 text-red-500" />
          </div>
          <h3 class="text-lg font-bold text-gray-900">Product not found</h3>
          <p class="text-gray-500 mt-1 mb-6">{{ error }}</p>
          <RouterLink to="/" class="px-6 py-2 bg-black text-white rounded-full text-sm font-medium hover:bg-gray-800">
            Back to Home
          </RouterLink>
        </div>

        <div v-else-if="product" class="grid grid-cols-1 lg:grid-cols-12 gap-10 xl:gap-16">
          
          <div class="lg:col-span-7 space-y-6">
            <div class="aspect-[4/3] w-full bg-gray-50 rounded-3xl border border-gray-100 flex items-center justify-center p-8 overflow-hidden relative group">
              <img
                :src="selectedImage || fullImage"
                :alt="product.product_name"
                class="w-full h-full object-contain mix-blend-multiply transition-transform duration-500 group-hover:scale-105"
              />
              <div v-if="!inStock" class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                Out of Stock
              </div>
            </div>

            <div class="hidden lg:block pt-8 border-t border-gray-100">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Product Description</h3>
              <div class="prose prose-sm text-gray-600 max-w-none whitespace-pre-line leading-relaxed">
                {{ product.description || 'No description available.' }}
              </div>
            </div>
          </div>

          <div class="lg:col-span-5 space-y-8">
            
            <div>
              <p class="text-sm text-red-500 font-bold uppercase tracking-wider mb-1">{{ product.category?.name || 'Electronics' }}</p>
              <h1 class="text-3xl md:text-4xl font-black text-gray-900 leading-tight mb-3">
                {{ product.product_name }}
              </h1>
              
              <div class="flex items-center flex-wrap gap-4 text-sm">
                <div class="flex items-center gap-1 bg-yellow-50 px-2 py-1 rounded-md border border-yellow-100">
                  <StarIcon class="w-4 h-4 text-yellow-500" />
                  <span class="font-bold text-yellow-700">{{ avgRating.toFixed(1) }}</span>
                  <span class="text-yellow-600/70">({{ reviewCount }} reviews)</span>
                </div>
                
                <span class="h-4 w-px bg-gray-300"></span>
                
                <span 
                  class="flex items-center gap-1.5 font-medium"
                  :class="inStock ? 'text-green-600' : 'text-red-600'"
                >
                  <CheckCircleIcon v-if="inStock" class="w-4 h-4" />
                  <XCircleIcon v-else class="w-4 h-4" />
                  {{ inStock ? `In Stock (${product.stock} units)` : 'Out of Stock' }}
                </span>
              </div>
            </div>

            <div class="flex items-baseline gap-4">
              <span class="text-4xl font-bold text-gray-900 tracking-tight">
                {{ formatPrice(product.price) }}
              </span>
              </div>

            <div class="border-t border-gray-100"></div>

            <div class="space-y-6">
              <div class="flex items-center gap-4">
                <div class="flex items-center border border-gray-200 rounded-full px-2 py-1 w-fit shadow-sm hover:shadow-md transition-shadow bg-white">
  
                  <button 
                    @click="decreaseQty" 
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-500 hover:text-black hover:bg-gray-100 transition-all active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
                    :disabled="quantity <= 1"
                  >
                    <span class="text-lg font-medium leading-none mb-0.5">-</span>
                  </button>

                  <input 
                    type="number" 
                    :value="quantity" 
                    @input="handleQtyInput"
                    class="w-12 text-center bg-transparent font-bold text-gray-900 text-sm focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                  />

                  <button 
                    @click="increaseQty" 
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-500 hover:text-black hover:bg-gray-100 transition-all active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
                    :disabled="!inStock || quantity >= product.stock"
                  >
                    <span class="text-lg font-medium leading-none mb-0.5">+</span>
                  </button>
                  
                </div>

                <button 
                  @click="toggleWishlist"
                  class="group flex items-center gap-2 px-4 py-2 rounded-full border border-gray-200 hover:border-red-200 hover:bg-red-50 transition-all"
                >
                  <HeartIcon 
                    class="w-5 h-5 transition-colors" 
                    :class="isFavorite ? 'text-red-500' : 'text-gray-400 group-hover:text-red-500'" 
                  />
                  <span class="text-sm font-medium" :class="isFavorite ? 'text-red-600' : 'text-gray-600'">
                    {{ isFavorite ? 'Saved' : 'Wishlist' }}
                  </span>
                </button>
              </div>

              <div class="flex flex-col sm:flex-row gap-3">
                <button
                  @click="addToCart"
                  :disabled="!inStock"
                  class="flex-1 px-8 py-4 bg-black text-white rounded-xl font-bold text-sm shadow-xl hover:bg-gray-900 hover:shadow-2xl hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 transition-all"
                >
                  Add to Cart
                </button>
                <button
                  @click="buyNow"
                  :disabled="!inStock"
                  class="flex-1 px-8 py-4 bg-black text-white rounded-xl font-bold text-sm shadow-xl hover:bg-gray-900 hover:shadow-2xl hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 transition-all"
                >
                  Buy Now
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-6">
              <div class="p-4 border border-gray-100 rounded-xl bg-gray-50/50 flex items-start gap-3">
                <div class="text-2xl">🚚</div>
                <div>
                  <h4 class="font-bold text-sm text-gray-900">Free Delivery</h4>
                  <p class="text-xs text-gray-500 mt-0.5">Enter your postal code for availability.</p>
                </div>
              </div>
              <div class="p-4 border border-gray-100 rounded-xl bg-gray-50/50 flex items-start gap-3">
                <div class="text-2xl">🛡️</div>
                <div>
                  <h4 class="font-bold text-sm text-gray-900">Return Delivery</h4>
                  <p class="text-xs text-gray-500 mt-0.5">Free 30 Days Delivery Returns.</p>
                </div>
              </div>
            </div>

            <div class="lg:hidden pt-8 border-t border-gray-100">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Description</h3>
              <div class="prose prose-sm text-gray-600 whitespace-pre-line leading-relaxed">
                {{ product.description || 'Tidak ada deskripsi tersedia.' }}
              </div>
            </div>

            <div class="pt-8 border-t border-gray-100 space-y-8">
              
              <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-4">Write a Review</h3>
                
                <div class="flex items-center gap-2 mb-4">
                  <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Rating</span>
                  <div class="flex">
                    <button 
                      v-for="star in 5" 
                      :key="star"
                      @click="newReview.rating = star"
                      class="p-1 focus:outline-none transition-transform hover:scale-110"
                    >
                      <StarIcon 
                        class="w-6 h-6" 
                        :class="star <= (newReview.rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                      />
                    </button>
                  </div>
                  <button 
                    v-if="newReview.rating" 
                    @click="newReview.rating = null"
                    class="text-xs text-red-500 underline ml-auto font-medium"
                  >
                    Reset (Auto-AI)
                  </button>
                </div>

                <textarea
                  v-model="newReview.text"
                  rows="3"
                  class="w-full bg-white border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all mb-3 resize-none"
                  placeholder="Ceritakan pengalaman Anda menggunakan produk ini..."
                ></textarea>

                <div v-if="submitError" class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg mb-3">
                  {{ submitError }}
                </div>

                <button
                  @click="submitReview"
                  :disabled="isSubmitting || (!newReview.text && !newReview.rating)"
                  class="w-full bg-black text-white text-sm font-bold py-3 rounded-xl hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                  {{ isSubmitting ? 'Submitting...' : 'Submit Review' }}
                </button>
              </div>

              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <span>AI Sentiment Analysis</span>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] rounded-full uppercase tracking-wider">Beta</span>
                  </h3>
                </div>

                <div class="space-y-3">
                  <div class="space-y-1">
                    <div class="flex justify-between text-xs font-medium">
                      <span class="text-emerald-700">Positive 😊</span>
                      <span class="text-emerald-700">{{ sentimentPercent.positive }}%</span>
                    </div>
                    <div class="h-2 w-full bg-emerald-50 rounded-full overflow-hidden">
                      <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" :style="{ width: sentimentPercent.positive + '%' }"></div>
                    </div>
                  </div>

                  <div class="space-y-1">
                    <div class="flex justify-between text-xs font-medium">
                      <span class="text-amber-700">Neutral 😐</span>
                      <span class="text-amber-700">{{ sentimentPercent.neutral }}%</span>
                    </div>
                    <div class="h-2 w-full bg-amber-50 rounded-full overflow-hidden">
                      <div class="h-full bg-amber-400 rounded-full transition-all duration-1000" :style="{ width: sentimentPercent.neutral + '%' }"></div>
                    </div>
                  </div>

                  <div class="space-y-1">
                    <div class="flex justify-between text-xs font-medium">
                      <span class="text-rose-700">Negative 😟</span>
                      <span class="text-rose-700">{{ sentimentPercent.negative }}%</span>
                    </div>
                    <div class="h-2 w-full bg-rose-50 rounded-full overflow-hidden">
                      <div class="h-full bg-rose-500 rounded-full transition-all duration-1000" :style="{ width: sentimentPercent.negative + '%' }"></div>
                    </div>
                  </div>
                </div>
                <p class="text-[10px] text-gray-400 italic text-center mt-2">
                  *Sentiment analysis is generated automatically by AI based on user reviews.
                </p>
              </div>

              <div>
                <h3 class="font-bold text-gray-900 mb-6 flex items-center justify-between">
                  Customer Reviews
                  <span class="text-sm font-normal text-gray-500">{{ reviewCount }} total</span>
                </h3>

                <div v-if="reviewsLoading" class="text-center py-8">
                  <div class="inline-block w-6 h-6 border-2 border-gray-200 border-t-black rounded-full animate-spin"></div>
                </div>

                <div v-else-if="!reviews.length" class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                  <p class="text-sm text-gray-500">No reviews yet. Be the first to review!</p>
                </div>

                <div v-else class="space-y-4">
                  <div
                    v-for="rev in reviews"
                    :key="rev.review_id"
                    class="p-5 border border-gray-100 rounded-2xl bg-white hover:shadow-sm transition-shadow"
                  >
                    <div class="flex justify-between items-start mb-3">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-500 text-sm">
                          {{ (rev.buyer?.name || 'U').charAt(0).toUpperCase() }}
                        </div>
                        
                        <div>
                          <p class="font-bold text-sm text-gray-900">{{ rev.buyer?.name || 'Verified Buyer' }}</p>
                          
                          <div class="flex items-center gap-2 mt-1">
                            <div class="flex text-yellow-400">
                              <StarIcon v-for="n in 5" :key="n" class="w-3 h-3" :class="n <= rev.rating ? 'fill-current' : 'text-gray-200'" />
                            </div>
                            <span class="text-xs text-gray-300">|</span>
                            <span class="text-xs text-gray-400">
                              {{ new Date(rev.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                            </span>
                            <span 
                              v-if="getSentimentBadge(rev.sentiment)"
                              class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full border"
                              :class="getSentimentBadge(rev.sentiment).classes"
                            >
                              {{ getSentimentBadge(rev.sentiment).label }}
                            </span>
                          </div>
                        </div>
                      </div>

                      <button 
                        v-if="auth.user && auth.user.user_id === rev.buyer_id"
                        @click="deleteReview(rev)"
                        class="text-xs font-bold text-gray-400 hover:text-red-600 transition-colors"
                        title="Hapus Ulasan Anda"
                      >
                        <TrashIcon class="w-4 h-4" /> Delete
                      </button>
                    </div>
                    
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                      {{ rev.review_text }}
                    </p>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
