<script setup>
import { ref, computed, onMounted } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import ProductCard from '../components/ProductCard.vue'
import { api } from '../lib/api'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuth()
const r = useRouter()

const products = ref([])
const loading = ref(true)
const error = ref('')
const openDetail = (product) => {
  if (!product?.product_id) return
  r.push(`/products/${product.product_id}`)
  // atau kalau pakai route name:
  // r.push({ name: 'product-detail', params: { id: product.product_id } })
}

// Ambil data dari backend TANPA paksa login
onMounted(async () => {
  try {
    const res = await api.get('/products')
    products.value = res.data?.data ?? []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Gagal memuat produk.'
  } finally {
    loading.value = false
  }
})

const bestSelling = computed(() => products.value.slice(0, 4))

const exploreProducts = computed(() => {
  if (products.value.length <= 4) {
    return products.value
  }
  return products.value.slice(4)
})
</script>


<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1">
      <!-- HERO SECTION -->
      <section class="w-full bg-black">
        <div class="max-w-6xl mx-auto px-4 lg:px-0 py-14">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
            <!-- Left text -->
            <div class="text-white space-y-4">
              <p class="uppercase tracking-wider text-sm text-gray-300">
                iPhone 14 Series
              </p>
              <h1 class="text-3xl md:text-4xl font-bold leading-tight">
                Up to 10% off Voucher
              </h1>
              <button
                class="bg-white text-black px-6 py-2 rounded-full font-semibold hover:bg-gray-200 mt-4 inline-flex items-center gap-2"
              >
                Shop Now →
              </button>
            </div>

            <!-- Right image -->
            <div class="flex justify-center">
              <img
                class="rounded-xl w-full max-w-md"
                src="/hero-banner.png"
                alt="banner"
              />
            </div>
          </div>

          <!-- slider dots -->
          <div class="flex gap-2 mt-6 justify-center">
            <div class="w-2 h-2 bg-white rounded-full"></div>
            <div class="w-2 h-2 bg-white/40 rounded-full"></div>
            <div class="w-2 h-2 bg-white/40 rounded-full"></div>
            <div class="w-2 h-2 bg-white/40 rounded-full"></div>
          </div>
        </div>
      </section>

      <!-- BEST SELLING -->
      <section class="max-w-6xl mx-auto px-4 lg:px-0 mt-12">
        <div class="flex justify-between items-center mb-6">
          <div>
            <p class="text-red-500 font-semibold text-sm flex items-center gap-2">
              <span class="w-1.5 h-4 bg-red-500 rounded-full"></span>
              This Month
            </p>
            <h2 class="text-2xl font-bold mt-1">Best Selling Products</h2>
          </div>
          <button class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
            View All
          </button>
        </div>

        <!-- STATE -->
        <div v-if="loading" class="py-12 text-center text-gray-500">
          Loading...
        </div>
        <div v-else-if="error" class="py-12 text-center text-red-500">
          {{ error }}
        </div>

        <!-- PRODUCTS -->
        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <ProductCard
            v-for="p in bestSelling"
            :key="p.product_id"
            :product="p"
                @open="openDetail"
          />
        </div>
      </section>

      <!-- EXPLORE -->
      <section class="max-w-6xl mx-auto px-4 lg:px-0 mt-16 mb-16">
        <div class="flex justify-between items-center mb-6">
          <div>
            <p class="text-red-500 font-semibold text-sm flex items-center gap-2">
              <span class="w-1.5 h-4 bg-red-500 rounded-full"></span>
              Our Products
            </p>
            <h2 class="text-2xl font-bold mt-1">Explore Our Products</h2>
          </div>

          <div class="flex gap-2">
            <button
              class="w-9 h-9 border rounded-full hover:border-black hover:text-black text-gray-600"
            >
              ←
            </button>
            <button
              class="w-9 h-9 border rounded-full hover:border-black hover:text-black text-gray-600"
            >
              →
            </button>
          </div>
        </div>

        <div v-if="!loading" class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <ProductCard
            v-for="p in exploreProducts"
            :key="p.product_id"
            :product="p"
                @open="openDetail"
          />
        </div>

        <div class="flex justify-center mt-10">
          <button class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600">
            View All Products
          </button>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
