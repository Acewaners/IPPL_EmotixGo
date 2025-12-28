<script setup>
import { ref, computed, onMounted } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import ProductCard from '../components/ProductCard.vue'
import { api } from '../lib/api'

// STATE
const products = ref([])
const categories = ref([])
const loading = ref(true)
const selectedCategory = ref('All') // Default tampilkan semua

// FETCH DATA
const fetchData = async () => {
  loading.value = true
  try {
    // 1. Ambil semua produk
    // Asumsi endpoint backend adalah /products untuk mengambil semua barang
    const resProducts = await api.get('/products')
    products.value = resProducts.data.data || resProducts.data

    // 2. Ambil kategori (opsional, jika ingin dinamis dari DB)
    // Jika backend belum siap, kita bisa hardcode kategori di bawah
    const resCat = await api.get('/categories')
    categories.value = resCat.data.data || resCat.data
    
  } catch (error) {
    console.error("Gagal mengambil data:", error)
  } finally {
    loading.value = false
  }
}

// FILTER LOGIC
const filteredProducts = computed(() => {
  if (selectedCategory.value === 'All') {
    return products.value
  }
  // Filter berdasarkan nama kategori
  return products.value.filter(product => {
    // Pastikan product.category ada dan cocokkan namanya
    return product.category?.name?.toLowerCase() === selectedCategory.value.toLowerCase()
  })
})

// Kategori Manual (Sesuai request Anda: Handphone, Tablet, Laptop)
// Kita gabungkan dengan opsi 'All'
const filterOptions = computed(() => {
  // Jika mau pakai data asli dari DB:
  // return ['All', ...categories.value.map(c => c.name)]
  
  // Jika mau hardcode sesuai request:
  return ['All', 'Handphone', 'Tablet', 'Laptop']
})

const setCategory = (cat) => {
  selectedCategory.value = cat
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 max-w-7xl mx-auto px-4 py-8 w-full">
      <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-100 py-4 mb-8 -mx-4 px-4 md:px-8 transition-all duration-300">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
          
          <div class="flex flex-col items-start">
            <span class="text-[10px] font-bold tracking-widest text-gray-400 uppercase mb-1">
              Catalog
            </span>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                Semua Produk
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                {{ filteredProducts.length }}
              </span>
            </div>
          </div>

          <div class="flex flex-wrap gap-1 p-1.5 bg-gray-100/80 rounded-full border border-gray-200/50">
            <button 
              v-for="cat in filterOptions" 
              :key="cat"
              @click="setCategory(cat)"
              class="px-5 py-2 rounded-full text-xs md:text-sm font-medium transition-all duration-300 relative"
              :class="selectedCategory === cat 
                ? 'bg-white text-black shadow-sm ring-1 ring-black/5 scale-100' 
                : 'bg-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-200/50'"
            >
              <span v-if="selectedCategory === cat" class="absolute -top-1 -right-1 flex h-2 w-2">
              </span>
              
              {{ cat }}
            </button>
          </div>

        </div>
      </div>

      <div v-if="loading" class="text-center py-20">
        <p class="text-gray-500">Memuat produk...</p>
      </div>

      <div 
        v-else-if="filteredProducts.length === 0" 
        class="flex flex-col items-center justify-center py-24 px-4 text-center border-2 border-dashed border-gray-200 rounded-3xl bg-gray-50/50"
      >
        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-sm mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
          </svg>
        </div>

        <h3 class="text-xl font-bold text-gray-900 mb-2">
          Produk tidak ditemukan
        </h3>
        <p class="text-gray-500 max-w-md mx-auto mb-8">
          Sepertinya kami belum memiliki stok untuk kategori <span class="font-semibold text-black">"{{ selectedCategory }}"</span> saat ini. Yuk, intip koleksi lainnya!
        </p>

        <button 
          @click="selectedCategory = 'All'" 
          class="group flex items-center gap-2 px-6 py-3 bg-black text-white rounded-full font-medium transition-all hover:bg-gray-800 hover:shadow-lg hover:-translate-y-0.5"
        >
          <span>Lihat Semua Produk</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 transition-transform group-hover:translate-x-1">
            <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <ProductCard 
          v-for="product in filteredProducts" 
          :key="product.product_id || product.id" 
          :product="product" 
        />
      </div>
    </main>

    <Footer />
  </div>
</template>