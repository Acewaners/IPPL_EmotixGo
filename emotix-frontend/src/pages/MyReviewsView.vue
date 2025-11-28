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

  if (!text || rating < 1) return

  submitting.value = true
  savingId.value = productId
  error.value = ''

  try {
    await api.post('/reviews', {
      product_id: productId,
      review_text: text,
      rating,
    })

    drafts.value[productId] = ''
    ratingDrafts.value[productId] = 0
    await reloadReviews()
  } catch (e) {
    console.error(e)
    error.value =
      e?.response?.data?.message || 'Gagal mengirim review.'
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
  if (!text || rating < 1) return

  submitting.value = true
  error.value = ''

  try {
    await api.put(`/reviews/${editId.value}`, {
      review_text: text,
      rating,
    })
    editId.value = null
    editDraft.value = ''
    editRating.value = 0
    await reloadReviews()
  } catch (e) {
    console.error(e)
    error.value =
      e?.response?.data?.message || 'Gagal menyimpan perubahan review.'
  } finally {
    submitting.value = false
  }
}

onMounted(loadAll)
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 max-w-6xl mx-auto px-4 lg:px-0 py-10 space-y-8">
      <!-- Header -->
      <section class="space-y-1">
        <h1 class="text-xl font-semibold">My Reviews</h1>
        <p class="text-sm text-gray-500">
          Lihat dan tulis ulasan untuk produk yang sudah kamu beli, sayang 💚
        </p>
      </section>

      <!-- state -->
      <section v-if="loading" class="py-10 text-center text-gray-500 text-sm">
        Loading...
      </section>

      <section v-else-if="error" class="py-10 text-center text-red-500 text-sm">
        {{ error }}
      </section>

      <section v-else class="space-y-8">
        <!-- Belum direview -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold">Perlu Review</h2>
            <span class="text-xs text-gray-400">
              {{ needReview.length }} produk
            </span>
          </div>

          <div
            v-if="!needReview.length"
            class="text-xs text-gray-500 border rounded-lg px-4 py-3 bg-gray-50"
          >
            Semua produk dari pesanan yang selesai sudah kamu review.
          </div>

          <div class="space-y-4">
            <div
              v-for="item in needReview"
              :key="item.order.transaction_id + '-' + item.product.product_id"
              class="border rounded-lg p-4 flex flex-col gap-3 md:flex-row md:items-start"
            >
              <!-- kiri: produk -->
              <div class="flex items-start gap-3 md:w-1/3">
                <div
                  class="w-14 h-14 rounded border bg-gray-50 overflow-hidden flex items-center justify-center"
                >
                  <img
                    :src="productImage(item.product)"
                    alt=""
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="text-xs md:text-sm">
                  <p class="font-semibold">
                    {{ item.product.product_name }}
                  </p>
                  <p class="text-gray-500">
                    {{ formatPrice(item.product.price) }}
                  </p>
                  <p class="text-[11px] text-gray-400 mt-1">
                    Order #{{ item.order.transaction_id }} ·
                    {{ formatDateTime(item.order.transaction_date || item.order.created_at) }}
                  </p>
                </div>
              </div>

              <!-- kanan: form review -->
              <div class="md:flex-1 space-y-2">
                <!-- rating bintang -->
                <div class="flex items-center gap-1 text-lg">
                  <button
                    v-for="star in stars"
                    :key="star"
                    type="button"
                    @click="ratingDrafts[item.product.product_id] = star"
                  >
                    <span
                      :class="[
                        star <= (ratingDrafts[item.product.product_id] || 0)
                          ? 'text-yellow-400'
                          : 'text-gray-300'
                      ]"
                    >
                      ★
                    </span>
                  </button>
                  <span class="ml-2 text-[11px] text-gray-500">
                    {{ ratingDrafts[item.product.product_id] || 0 }}/5
                  </span>
                </div>

                <textarea
                  v-model="drafts[item.product.product_id]"
                  rows="3"
                  class="w-full border rounded-md px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
                  placeholder="Tuliskan pendapatmu tentang produk ini..."
                />
                <div class="flex justify-between items-center">
                  <span class="text-[11px] text-gray-400">
                    Minimal 5 karakter & pilih rating.
                  </span>
                  <button
                    type="button"
                    class="px-4 py-1.5 text-xs rounded-md bg-black text-white hover:bg-gray-900 disabled:opacity-50"
                    :disabled="
                      submitting ||
                      !drafts[item.product.product_id] ||
                      drafts[item.product.product_id].trim().length < 5 ||
                      (ratingDrafts[item.product.product_id] || 0) < 1
                    "
                    @click="submitReview(item.product.product_id)"
                  >
                    {{ savingId === item.product.product_id && submitting
                      ? 'Saving...'
                      : 'Submit Review' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sudah direview -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold">Review Saya</h2>
            <span class="text-xs text-gray-400">
              {{ reviewedList.length }} review
            </span>
          </div>

          <div
            v-if="!reviewedList.length"
            class="text-xs text-gray-500 border rounded-lg px-4 py-3 bg-gray-50"
          >
            Kamu belum menulis review apa pun.
          </div>

          <div class="space-y-4">
            <div
              v-for="rev in reviewedList"
              :key="rev.review_id"
              class="border rounded-lg p-4 flex gap-3"
            >
              <div
                class="w-14 h-14 rounded border bg-gray-50 overflow-hidden flex-shrink-0 flex items-center justify-center"
              >
                <img
                  :src="productImage(rev.product)"
                  alt=""
                  class="w-full h-full object-cover"
                />
              </div>

              <div class="flex-1 text-xs md:text-sm space-y-1">
                <div class="flex justify-between items-start gap-2">
                  <div class="space-y-1">
                    <p class="font-semibold">
                      {{ rev.product?.product_name || 'Unknown Product' }}
                    </p>

                    <!-- rating tampilan -->
                    <div class="flex items-center gap-1 text-yellow-400 text-sm">
                      <span
                        v-for="star in stars"
                        :key="star"
                      >
                        {{ star <= (rev.rating || 0) ? '★' : '☆' }}
                      </span>
                      <span class="ml-1 text-[11px] text-gray-500 text-yellow-900">
                        {{ rev.rating || 0 }}/5
                      </span>
                    </div>

                    <p class="text-[11px] text-gray-400">
                      {{ formatDateTime(rev.created_at) }}
                    </p>
                  </div>

                  <button
                    type="button"
                    class="text-[11px] text-blue-600 hover:underline"
                    @click="startEdit(rev)"
                  >
                    Edit
                  </button>
                </div>

                <!-- mode edit -->
                <div v-if="editId === rev.review_id" class="space-y-2 mt-1">
                  <!-- rating edit -->
                  <div class="flex items-center gap-1 text-lg">
                    <button
                      v-for="star in stars"
                      :key="star"
                      type="button"
                      @click="editRating = star"
                    >
                      <span
                        :class="[
                          star <= (editRating || 0)
                            ? 'text-yellow-400'
                            : 'text-gray-300'
                        ]"
                      >
                        ★
                      </span>
                    </button>
                    <span class="ml-2 text-[11px] text-gray-500">
                      {{ editRating || 0 }}/5
                    </span>
                  </div>

                  <textarea
                    v-model="editDraft"
                    rows="3"
                    class="w-full border rounded-md px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
                  />
                  <div class="flex justify-end gap-2">
                    <button
                      type="button"
                      class="px-3 py-1.5 text-[11px] rounded-md border hover:bg-gray-50"
                      @click="cancelEdit"
                    >
                      Cancel
                    </button>
                    <button
                      type="button"
                      class="px-3 py-1.5 text-[11px] rounded-md bg-black text-white hover:bg-gray-900 disabled:opacity-50"
                      :disabled="submitting || !editDraft.trim() || (editRating || 0) < 1"
                      @click="saveEdit"
                    >
                      {{ submitting ? 'Saving...' : 'Save' }}
                    </button>
                  </div>
                </div>

                <!-- mode view -->
                <p
                  v-else
                  class="text-gray-700 whitespace-pre-line mt-1"
                >
                  {{ rev.review_text }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
