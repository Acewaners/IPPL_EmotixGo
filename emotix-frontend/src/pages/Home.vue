<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import ProductCard from '../components/ProductCard.vue'
import { api } from '../lib/api'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuth()
const r = useRouter()
const currentSlide = ref(0)
const sliderInterval = ref(null)

const slides = [
  {
    tag: 'iPhone 14 Series',
    title: 'Up to 10% off Voucher',
    desc: 'Dapatkan diskon spesial untuk pembelian hari ini.',
    image: '/hero-banner.png',
    bg: 'bg-black' 
  },
  {
    tag: 'Super Sale',
    title: 'New Collection 2025',
    desc: 'Upgrade gadgetmu dengan teknologi AI terbaru.',
    image: '/hero-banner.png',
    bg: 'bg-black' 
  },
  {
    tag: 'Special Offer',
    title: 'Free Shipping All Item',
    desc: 'Belanja tanpa pusing mikirin ongkir ke seluruh Indonesia.',
    image: '/hero-banner.png',
    bg: 'bg-black' 
  }
]

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

const exploreContainer = ref(null)

// 2. Fungsi untuk handle klik tombol
const scrollExplore = (direction) => {
  const container = exploreContainer.value
  if (!container) return

  // Tentukan jarak scroll (misal: lebar container dibagi 2, atau angka tetap misal 300px)
  const scrollAmount = container.clientWidth / 2

  if (direction === 'left') {
    container.scrollBy({ left: -scrollAmount, behavior: 'smooth' })
  } else {
    container.scrollBy({ left: scrollAmount, behavior: 'smooth' })
  }
}

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % slides.length
}

const setSlide = (index) => {
  currentSlide.value = index
  resetTimer() // Reset timer kalau user klik manual
}

const startTimer = () => {
  sliderInterval.value = setInterval(nextSlide, 5000) // Geser tiap 5 detik
}

const resetTimer = () => {
  clearInterval(sliderInterval.value)
  startTimer()
}

// ... kode onMounted fetch produk yang sudah ada ...

// Tambahkan startTimer di onMounted
onMounted(async () => {
  startTimer()
  // ... fetch data produk ...
})

// Matikan timer saat pindah halaman
onUnmounted(() => {
  clearInterval(sliderInterval.value)
})

</script>


<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1">
      <!-- HERO SECTION -->
      <section class="w-full bg-black relative overflow-hidden group">
        
        <div 
          class="flex transition-transform duration-700 ease-in-out h-[500px] md:h-[450px]"
          :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
        >
          <div 
            v-for="(slide, index) in slides" 
            :key="index"
            class="min-w-full flex items-center justify-center px-4 md:px-0"
          >
            <div class="max-w-6xl w-full mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
              
              <div class="space-y-6 pl-4 md:pl-0 order-2 md:order-1 text-center md:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-gray-700 bg-gray-900/50 backdrop-blur-sm">
                   <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                   <p class="uppercase tracking-widest text-xs font-bold text-gray-300">
                     {{ slide.tag }}
                   </p>
                </div>

                <h1 class="text-4xl md:text-6xl font-black text-white leading-tight tracking-tight">
                  {{ slide.title }}
                </h1>
                
                <p class="text-gray-400 text-lg max-w-lg mx-auto md:mx-0">
                  {{ slide.desc }}
                </p>
                
                <button
                  class="group bg-white text-black px-8 py-3 rounded-full font-bold hover:bg-gray-200 transition-all active:scale-95 inline-flex items-center gap-2 mt-2"
                >
                  Shop Now 
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 transition-transform group-hover:translate-x-1">
                    <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>

              <div class="flex justify-center order-1 md:order-2 relative">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-500/20 to-purple-500/20 blur-3xl rounded-full opacity-50 animate-pulse"></div>
                
                <img
                  class="relative z-10 w-full max-w-[280px] md:max-w-md object-contain drop-shadow-2xl transition-transform duration-700 hover:scale-105"
                  :src="slide.image"
                  alt="Hero Banner"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-3 z-20">
          <button 
            v-for="(slide, index) in slides" 
            :key="index"
            @click="setSlide(index)"
            class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"
            :class="currentSlide === index ? 'w-8 bg-white' : 'w-2 bg-gray-600 hover:bg-gray-400'"
            aria-label="Go to slide"
          ></button>
        </div>

      </section>

      <!-- BEST SELLING -->
      <section class="max-w-6xl mx-auto px-4 lg:px-0 mt-12">
        <div class="flex justify-between items-center mb-6">
          <div class="space-y-4">
            <div class="flex items-center gap-2 text-red-500 font-semibold">
              <div class="w-5 h-10 bg-red-500 rounded"></div>
              <span>This Month</span>
            </div>
            <h2 class="text-3xl font-bold">Best Selling Products</h2>
          </div>
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
        <div class="flex justify-between items-end mb-8">
          <div class="space-y-4">
            <div class="flex items-center gap-2 text-red-500 font-semibold">
              <div class="w-5 h-10 bg-red-500 rounded"></div>
              <span>Our Products</span>
            </div>
            <h2 class="text-3xl font-bold">Explore Our Products</h2>
          </div>

          <div class="flex gap-2">
            <button 
              @click="scrollExplore('left')" 
              class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
              </svg>
            </button>
            
            <button 
              @click="scrollExplore('right')"
              class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </button>
          </div>
        </div>

        <div 
          ref="exploreContainer"
          class="flex gap-6 overflow-x-auto pb-6 scroll-smooth no-scrollbar"
        >
          <ProductCard 
            v-for="product in products" 
            :key="product.id" 
            :product="product" 
            class="min-w-[250px] md:min-w-[280px]" 
          /> 
          </div>

        <div class="mt-10 flex justify-start">
          <RouterLink 
            to="/products"
            class="group relative inline-flex items-center gap-3 px-8 py-4 bg-black text-white text-sm font-medium rounded-full transition-all duration-300 hover:bg-gray-800 hover:shadow-lg hover:-translate-y-1"
          >
            <span>View All Products</span>
            
            <svg 
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke-width="2" 
              stroke="currentColor" 
              class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </RouterLink>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
