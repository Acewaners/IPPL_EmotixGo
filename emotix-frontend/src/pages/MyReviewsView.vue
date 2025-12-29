<script setup>
import { ref, computed, onMounted } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { api } from '../lib/api'

const orders = ref([])
const myReviews = ref([])

const loading = ref(true)
const error = ref('')

const submitting = ref(false)
const savingId = ref(null)   // product_id yg lagi di-submit
const editId = ref(null)     // review_id yg lagi diedit

// draft text & rating untuk produk yang belum direview
const drafts = ref({})          // product_id -> teks
const ratingDrafts = ref({})    // product_id -> rating (1–5)

// draft text & rating saat edit review
const editDraft = ref('')
const editRating = ref(0)

// base url gambar
const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'

const productImage = (p) => {
  if (!p) return ''
  if (p.image_full) return p.image_full
  if (p.image_url) return p.image_url
  if (!p.image) return ''
  if (p.image.startsWith('http')) return p.image
  return `${STORAGE_BASE}/${p.image}`
}

const formatPrice = (v) =>
  `Rp ${Number(v || 0).toLocaleString('id-ID')}`

const formatDateTime = (v) => {
  if (!v) return '-'
  return new Date(v).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    const [trxRes, revRes] = await Promise.all([
      api.get('/buyer/transactions'),
      api.get('/reviews/me'),
    ])

    const trxRaw = trxRes.data?.data ?? trxRes.data
    orders.value = Array.isArray(trxRaw?.data) ? trxRaw.data : trxRaw

    const revRaw = revRes.data?.data ?? revRes.data
    myReviews.value = Array.isArray(revRaw?.data) ? revRaw.data : revRaw
  } catch (e) {
    console.error(e)
    error.value =
      e?.response?.data?.message || 'Gagal memuat data review.'
  } finally {
    loading.value = false
  }
}

const reloadReviews = async () => {
  try {
    const res = await api.get('/reviews/me')
    const raw = res.data?.data ?? res.data
    myReviews.value = Array.isArray(raw?.data) ? raw.data : raw
  } catch (e) {
    console.error(e)
  }
}

// set berisi product_id yang sudah ada review
const reviewedProductIds = computed(() => {
  const set = new Set()
  myReviews.value.forEach((r) => set.add(r.product_id))
  return set
})

// produk dari order completed yang belum direview
const needReview = computed(() => {
  const result = []

  orders.value.forEach((o) => {
    if (o.status !== 'completed') return

    ;(o.details || []).forEach((d) => {
      const p = d.product
      if (!p) return
      if (reviewedProductIds.value.has(p.product_id)) return

      result.push({
        order: o,
        detail: d,
        product: p,
      })
    })
  })

  return result
})

// daftar review yg sudah dikirim
const reviewedList = computed(() => myReviews.value)

// helper bintang
const stars = [1, 2, 3, 4, 5]

const submitReview = async (productId) => {
  const text = (drafts.value[productId] || '').trim()
  const rating = ratingDrafts.value[productId] || 0

  // Validasi: Salah satu harus ada
  if (!text && !rating) {
    alert("Mohon isi bintang atau teks review.");
    return;
  }

  submitting.value = true
  savingId.value = productId
  error.value = ''

  try {
    await api.post('/reviews', {
      product_id: productId,
      review_text: text || null,
      rating: rating || null,
    })

    // Reset & Reload
    drafts.value[productId] = ''
    ratingDrafts.value[productId] = 0
    await reloadReviews()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || 'Gagal mengirim review.')
  } finally {
    submitting.value = false
    savingId.value = null
  }
}

const startEdit = (rev) => {
  editId.value = rev.review_id
  editDraft.value = rev.review_text
  editRating.value = rev.rating || 0
}

const cancelEdit = () => {
  editId.value = null
  editDraft.value = ''
  editRating.value = 0
}

const saveEdit = async () => {
  if (!editId.value) return
  const text = editDraft.value.trim()
  const rating = editRating.value || 0
  
  // Validasi edit
  if (!text && !rating) {
      alert("Review tidak boleh kosong total.");
      return;
  }

  submitting.value = true
  error.value = ''

  try {
    await api.put(`/reviews/${editId.value}`, {
      review_text: text || null,
      rating: rating || null,
    })
    
    // Reset state
    editId.value = null
    editDraft.value = ''
    editRating.value = 0
    await reloadReviews()
  } catch (e) {
    console.error(e)
    alert(e?.response?.data?.message || 'Gagal menyimpan perubahan.')
  } finally {
    submitting.value = false
  }
}

onMounted(loadAll)
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
      
      <div class="mb-8">
        <nav class="text-sm text-gray-500 mb-4 flex items-center gap-2">
          <RouterLink to="/" class="hover:text-black transition-colors">Home</RouterLink>
          <span class="text-gray-300">/</span>
          <span class="font-semibold text-black">My Reviews</span>
        </nav>
        <h1 class="text-3xl font-black tracking-tight text-gray-900">Ulasan Saya</h1>
        <p class="text-gray-500 mt-1">Kelola ulasan untuk produk yang telah Anda beli.</p>
      </div>

      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-black"></div>
        <p class="mt-4 text-sm text-gray-500">Memuat data ulasan...</p>
      </div>

      <div v-else-if="error" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-center gap-3 text-red-700">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 shrink-0"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-medium">{{ error }}</span>
      </div>

      <div v-else class="space-y-12">
        
        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900">Menunggu Ulasan</h2>
            <span class="bg-black text-white text-xs font-bold px-2 py-1 rounded-md">{{ needReview.length }}</span>
          </div>

          <div v-if="!needReview.length" class="bg-white border border-dashed border-gray-300 rounded-2xl p-10 text-center">
            <p class="text-gray-500 text-sm">Tidak ada produk yang perlu diulas saat ini.</p>
          </div>

          <div v-else class="grid gap-6">
            <div
              v-for="item in needReview"
              :key="item.order.transaction_id + '-' + item.product.product_id"
              class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-6 hover:shadow-md transition-shadow duration-300"
            >
              <div class="flex flex-col md:flex-row gap-6">
                <div class="flex items-start gap-4 md:w-1/3">
                  <div class="w-20 h-20 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center shrink-0">
                    <img
                      :src="productImage(item.product)"
                      :alt="item.product.product_name"
                      class="w-full h-full object-contain p-2 mix-blend-multiply"
                    />
                  </div>
                  <div>
                    <h3 class="font-bold text-gray-900 text-sm line-clamp-2">{{ item.product.product_name }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Order #{{ item.order.transaction_id }}</p>
                    <p class="text-xs text-gray-400">{{ formatDateTime(item.order.transaction_date || item.order.created_at) }}</p>
                  </div>
                </div>

                <div class="flex-1 border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6">
                  <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Rating</span>
                    <div class="flex">
                      <button
                        v-for="star in stars"
                        :key="star"
                        type="button"
                        @click="ratingDrafts[item.product.product_id] = star"
                        class="p-1 focus:outline-none transition-transform active:scale-90 hover:scale-110"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6" :class="star <= (ratingDrafts[item.product.product_id] || 0) ? 'text-yellow-400' : 'text-gray-200'">
                          <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </div>
                    <span v-if="ratingDrafts[item.product.product_id]" class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded ml-2">
                      {{ ratingDrafts[item.product.product_id] }}/5
                    </span>
                    <span v-else class="text-[10px] text-gray-400 ml-2">(Opsional - AI Auto Detect)</span>
                  </div>

                  <textarea
                    v-model="drafts[item.product.product_id]"
                    rows="3"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:bg-white focus:border-black focus:ring-1 focus:ring-black transition-all resize-none mb-3"
                    placeholder="Ceritakan pengalaman Anda menggunakan produk ini..."
                  ></textarea>

                  <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                      Minimal 5 karakter.
                    </p>
                    <button
                      type="button"
                      @click="submitReview(item.product.product_id)"
                      :disabled="submitting || (!drafts[item.product.product_id] && !ratingDrafts[item.product.product_id])"
                      class="bg-black text-white px-6 py-2.5 rounded-full text-xs font-bold hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow active:scale-95"
                    >
                      {{ savingId === item.product.product_id && submitting ? 'Mengirim...' : 'Kirim Ulasan' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900">Riwayat Ulasan</h2>
            <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded-md">{{ reviewedList.length }}</span>
          </div>

          <div v-if="!reviewedList.length" class="bg-white border border-dashed border-gray-300 rounded-2xl p-10 text-center">
            <p class="text-gray-500 text-sm">Belum ada ulasan yang Anda berikan.</p>
          </div>

          <div v-else class="grid gap-4">
            <div
              v-for="rev in reviewedList"
              :key="rev.review_id"
              class="bg-white rounded-2xl border border-gray-100 p-6 flex flex-col sm:flex-row gap-6 hover:border-gray-200 transition-colors"
            >
              <div class="w-16 h-16 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center shrink-0">
                <img
                  :src="productImage(rev.product)"
                  :alt="rev.product?.product_name"
                  class="w-full h-full object-contain p-2 mix-blend-multiply"
                />
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start mb-2">
                  <div>
                    <h3 class="font-bold text-gray-900 text-sm truncate pr-4">{{ rev.product?.product_name || 'Produk Tidak Dikenal' }}</h3>
                    <p class="text-xs text-gray-400">{{ formatDateTime(rev.created_at) }}</p>
                  </div>
                  <button
                    v-if="editId !== rev.review_id"
                    @click="startEdit(rev)"
                    class="text-xs font-bold text-black hover:underline"
                  >
                    Edit
                  </button>
                </div>

                <div v-if="editId === rev.review_id" class="mt-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
                  <div class="flex items-center gap-1 mb-3">
                    <button
                      v-for="star in stars"
                      :key="star"
                      type="button"
                      @click="editRating = star"
                      class="focus:outline-none"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 transition-colors" :class="star <= (editRating || 0) ? 'text-yellow-400' : 'text-gray-300'">
                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                      </svg>
                    </button>
                    <span class="text-xs font-medium text-gray-500 ml-2">{{ editRating }}/5</span>
                  </div>

                  <textarea
                    v-model="editDraft"
                    rows="3"
                    class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-black focus:ring-1 focus:ring-black mb-3"
                  ></textarea>

                  <div class="flex justify-end gap-3">
                    <button
                      type="button"
                      @click="cancelEdit"
                      class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-200 rounded-lg transition-colors"
                    >
                      Batal
                    </button>
                    <button
                      type="button"
                      @click="saveEdit"
                      :disabled="submitting || !editDraft.trim()"
                      class="px-4 py-2 text-xs font-bold text-white bg-black hover:bg-gray-800 rounded-lg transition-colors disabled:opacity-50"
                    >
                      {{ submitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                  </div>
                </div>

                <div v-else>
                  <div class="flex items-center gap-1 mb-2">
                    <svg v-for="star in stars" :key="star" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4" :class="star <= (rev.rating || 0) ? 'text-yellow-400' : 'text-gray-200'">
                      <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ rev.review_text }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>

      </div>
    </main>

    <Footer />
  </div>
</template>
