<script setup>
import { ref, onMounted, computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import Modal from '../components/Modal.vue'
import ProductForm from '../components/ProductForm.vue'
import { api } from '../lib/api'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuth()
const r = useRouter()

const products = ref([])
const categories = ref([])
const sellerOrders = ref([])
const loading = ref(true)
const error = ref('')

const openCreate = ref(false)
const openEdit = ref(false)
const editing = ref(null)

const STORAGE_BASE = import.meta.env.VITE_STORAGE_BASE || 'http://localhost:8000/storage'
const imgSrc = (path) => {
  if (!path) return '/dummy-qr.png' // Gambar default jika kosong
  if (path.startsWith('http')) return path // Jika URL lengkap (Cloudinary), pakai langsung
  return `${STORAGE_BASE}/${path}` // Jika path lokal, tambahkan base URL
}

onMounted(async () => {
  if (!auth.token) return r.push('/login')
  if (auth.user?.role !== 'seller') return r.push('/buyer/orders')
  await Promise.all([loadCategories(), loadProducts(), loadSellerOrders()])
})

const getProductImage = (imagePath) => {
  if (!imagePath) {
    return '/dummy-qr.png' // Gambar default jika kosong
  }
  
  // Jika gambar sudah berupa link lengkap (Cloudinary/External), langsung pakai
  if (imagePath.startsWith('http')) {
    return imagePath
  }
  
  // Jika gambar lokal (upload manual tanpa cloud), tambahkan prefix storage
  return `${STORAGE_BASE}/${imagePath}`
}

async function loadCategories() {
  try {
    const { data } = await api.get('/categories')
    categories.value = Array.isArray(data?.data)
      ? data.data
      : Array.isArray(data)
      ? data
      : []
  } catch (e) {
    console.error('Gagal memuat kategori:', e?.response?.data || e)
    categories.value = []
  }
}

async function loadProducts() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/products')
    const raw = Array.isArray(data?.data)
      ? data.data
      : Array.isArray(data)
      ? data
      : []
    products.value = raw.sort(
      (a, b) => (b.product_id ?? 0) - (a.product_id ?? 0),
    )
  } catch (e) {
    error.value = e?.response?.data?.message || 'Gagal memuat produk'
  } finally {
    loading.value = false
  }
}

async function loadSellerOrders() {
  try {
    const { data } = await api.get('/seller/orders')
    sellerOrders.value = Array.isArray(data?.data)
      ? data.data
      : Array.isArray(data)
      ? data
      : []
  } catch (e) {
    console.error('Gagal memuat pesanan seller:', e?.response?.data || e)
    sellerOrders.value = []
  }
}

async function createProduct(payload) {
  try {
    if (!payload.category_id) return alert('Kategori wajib dipilih')
    if (!payload.product_name?.trim()) return alert('Nama produk wajib')
    if (payload.price == null || Number(payload.price) < 0)
      return alert('Harga tidak valid')
    if (payload.stock == null || Number(payload.stock) < 0)
      return alert('Stok tidak valid')

    if (!payload.image) {
      await api.post('/products', {
        category_id: Number(payload.category_id),
        product_name: payload.product_name,
        price: Number(payload.price),
        stock: Number(payload.stock),
        description: payload.description ?? '',
      })
    } else {
      const form = new FormData()
      form.append('category_id', String(payload.category_id))
      form.append('product_name', payload.product_name)
      form.append('price', String(payload.price))
      form.append('stock', String(payload.stock))
      form.append('description', payload.description ?? '')
      form.append('image', payload.image)
      await api.post('/products', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    openCreate.value = false
    await loadProducts()
  } catch (e) {
    alert(e?.response?.data?.message || 'Gagal membuat produk')
  }
}

function startEdit(row) {
  editing.value = {
    product_id: row.product_id,
    category_id: row.category_id ?? '',
    product_name: row.product_name,
    price: row.price,
    stock: row.stock,
    description: row.description ?? '',
    image: null,
  }
  openEdit.value = true
}

async function updateProduct(payload) {
  try {
    const form = new FormData()
    form.append('_method', 'PUT')
    if (payload.category_id != null)
      form.append('category_id', String(payload.category_id))
    if (payload.product_name != null)
      form.append('product_name', payload.product_name ?? '')
    if (payload.price != null)
      form.append('price', String(payload.price ?? 0))
    if (payload.stock != null)
      form.append('stock', String(payload.stock ?? 0))
    if (payload.description != null)
      form.append('description', payload.description ?? '')
    if (payload.image) form.append('image', payload.image)

    await api.post(`/products/${editing.value.product_id}`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    openEdit.value = false
    await loadProducts()
  } catch (e) {
    alert(e?.response?.data?.message || 'Gagal update produk')
  }
}

async function removeProduct(row) {
  if (!confirm(`Hapus produk "${row.product_name}"?`)) return
  try {
    await api.delete(`/products/${row.product_id}`)
    await loadProducts()
  } catch (e) {
    alert(e?.response?.data?.message || 'Gagal hapus produk')
  }
}

/* ========== STATISTIK DASHBOARD ========== */

const totalRevenue = computed(() =>
  sellerOrders.value.reduce(
    (sum, o) => sum + Number(o.total_price ?? 0),
    0,
  ),
)

const totalOrders = computed(() => sellerOrders.value.length)

const totalProductsSold = computed(() =>
  sellerOrders.value.reduce((sum, o) => {
    const details = Array.isArray(o.details) ? o.details : []
    return (
      sum +
      details.reduce((s, d) => s + Number(d.quantity ?? 0), 0)
    )
  }, 0),
)

const activeProducts = computed(
  () => products.value.filter((p) => Number(p.stock ?? 0) > 0).length,
)
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 font-sans text-gray-900">
    <Navbar />

    <main class="flex-1">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
              Seller Dashboard
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              Manage your products, orders, and view your store performance.
            </p>
          </div>
          
          <button
            @click="openCreate = true"
            class="inline-flex items-center justify-center gap-2 bg-black text-white px-6 py-3 rounded-full text-sm font-medium hover:bg-gray-800 transition-all shadow-lg hover:shadow-xl active:scale-95"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add New Product
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
              <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Revenue</p>
              <p class="text-2xl font-extrabold text-gray-900 mt-2">
                Rp {{ totalRevenue.toLocaleString('id-ID') }}
              </p>
            </div>
            <div class="p-3 bg-green-50 rounded-xl text-green-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>

          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
              <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Orders</p>
              <p class="text-2xl font-extrabold text-gray-900 mt-2">
                {{ totalOrders }}
              </p>
            </div>
            <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 5c.07.286.074.58.074.857 0 .888-.363 1.633-.99 2.127C19.33 17.22 18.239 17.5 17.001 17.5c-1.296 0-2.396-.328-3.085-1.077-.13-.141-.19-.315-.19-.505v-4.257c0-.65-.526-1.168-1.185-1.168-1.184.282-1.854.282-2.924 0-.663 0-1.196.526-1.196 1.175v4.25c0 .185-.058.356-.182.493C7.576 17.16 6.47 17.5 5.166 17.5c-1.229 0-2.311-.272-2.909-.99-.62-.486-.98-1.221-.98-2.092 0-.295.008-.61.085-.92l1.378-5.32c.18-.696.88-1.177 1.597-1.177h12.72c.71 0 1.408.473 1.584 1.178z" />
              </svg>
            </div>
          </div>

          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
              <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Items Sold</p>
              <p class="text-2xl font-extrabold text-gray-900 mt-2">
                {{ totalProductsSold }}
              </p>
            </div>
            <div class="p-3 bg-orange-50 rounded-xl text-orange-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3.75h3.75M12 15.75h3.75M12 7.5V3.75m0 0A2.25 2.25 0 009.75 6H4.5a2.25 2.25 0 00-2.25 2.25v.844c0 .324.225.548.552.548h18.396c.328 0 .552-.224.552-.548v-.844A2.25 2.25 0 0019.5 6h-5.25a2.25 2.25 0 00-2.25-2.25z" />
              </svg>
            </div>
          </div>

          <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
              <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Products</p>
              <p class="text-2xl font-extrabold text-gray-900 mt-2">
                {{ activeProducts }}
              </p>
            </div>
            <div class="p-3 bg-purple-50 rounded-xl text-purple-600">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a22.53 22.53 0 005.246-5.246c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
            <h2 class="text-lg font-bold text-gray-900">Your Products</h2>
            </div>

          <div v-if="loading" class="p-10 text-center text-gray-500">
             <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-200 border-t-black"></div>
             <p class="mt-2 text-sm">Loading products...</p>
          </div>
          
          <div v-else-if="error" class="p-10 text-center text-red-500">
            {{ error }}
          </div>

          <div v-if="!loading && products.length" class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50/50">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Product</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Price</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Stock</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Sold</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Rating</th>
                  <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="p in products"
                  :key="p.product_id"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-4">
                      <div class="h-12 w-12 rounded-lg border border-gray-200 p-1 bg-white shrink-0 flex items-center justify-center overflow-hidden">
                        <img
                          v-if="p.image"
                          :src="imgSrc(p.image)"
                          class="w-full h-full object-contain mix-blend-multiply"
                          alt="Product"
                        />
                        <div v-else class="w-full h-full bg-gray-100 flex items-center justify-center text-gray-300">
                           <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        </div>
                      </div>
                      <div>
                        <p class="font-semibold text-gray-900 line-clamp-1">{{ p.product_name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ p.category?.name || p.category_name || 'Uncategorized' }}</p>
                      </div>
                    </div>
                  </td>

                  <td class="px-6 py-4 font-medium text-gray-900">
                    Rp {{ Number(p.price || 0).toLocaleString('id-ID') }}
                  </td>

                  <td class="px-6 py-4">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border"
                      :class="{
                        'bg-red-50 text-red-700 border-red-100': Number(p.stock ?? 0) <= 10,
                        'bg-yellow-50 text-yellow-700 border-yellow-100': Number(p.stock ?? 0) > 10 && Number(p.stock ?? 0) <= 50,
                        'bg-green-50 text-green-700 border-green-100': Number(p.stock ?? 0) > 50,
                      }"
                    >
                      {{ p.stock ?? 0 }} units
                    </span>
                  </td>

                  <td class="px-6 py-4 text-gray-600 font-medium">
                    {{ p.sold ?? 0 }}
                  </td>

                  <td class="px-6 py-4">
                    <div class="flex items-center gap-1">
                       <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                       <span class="font-semibold text-gray-900">{{ p.rating ?? 0 }}</span>
                       <span class="text-xs text-gray-400" v-if="p.rating_count">({{ p.rating_count }})</span>
                    </div>
                  </td>

                  <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        @click="startEdit(p)"
                        class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-black hover:border-black transition-colors shadow-sm"
                        title="Edit"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                      </button>
                      <button
                        @click="removeProduct(p)"
                        class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-colors shadow-sm"
                        title="Delete"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else-if="!loading" class="p-10 text-center flex flex-col items-center">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            <h3 class="text-gray-900 font-medium">No products found</h3>
            <p class="text-gray-500 text-sm mt-1 mb-4">Get started by creating your first product.</p>
            <button @click="openCreate = true" class="text-sm font-bold text-black hover:underline">Add Product</button>
          </div>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="p-6 border-b border-gray-100 bg-white">
            <h2 class="text-lg font-bold text-gray-900">Recent Orders</h2>
          </div>

          <div v-if="!sellerOrders.length" class="p-10 text-center flex flex-col items-center">
             <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
               <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
             </div>
            <h3 class="text-gray-900 font-medium">No orders yet</h3>
            <p class="text-gray-500 text-sm mt-1">Your recent sales will appear here.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50/50">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Order ID</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Customer</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Product</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Amount</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="o in sellerOrders"
                  :key="o.transaction_id"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-6 py-4 font-mono text-xs text-gray-500">
                    #ORD-{{ o.transaction_id }}
                  </td>
                  <td class="px-6 py-4 font-medium text-gray-900">
                    {{ o.buyer?.name || o.customer_name || '—' }}
                  </td>
                  <td class="px-6 py-4 text-gray-600">
                    <span class="line-clamp-1">
                      {{
                        o.details?.[0]?.product?.product_name ||
                        o.details?.[0]?.product_name ||
                        '—'
                      }}
                    </span>
                  </td>
                  <td class="px-6 py-4 font-medium text-gray-900">
                    Rp {{ Number(o.total_price || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="px-6 py-4 text-gray-500 text-xs">
                    {{ o.transaction_date || o.created_at }}
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border capitalize"
                      :class="{
                        'bg-green-50 text-green-700 border-green-100': o.status === 'completed' || o.status === 'success',
                        'bg-yellow-50 text-yellow-700 border-yellow-100': o.status === 'processing' || o.status === 'pending',
                        'bg-red-50 text-red-700 border-red-100': o.status === 'cancelled',
                        'bg-gray-100 text-gray-700 border-gray-200': !['completed', 'success', 'processing', 'pending', 'cancelled'].includes(o.status),
                      }"
                    >
                      <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                        :class="{
                           'bg-green-500': o.status === 'completed' || o.status === 'success',
                           'bg-yellow-500': o.status === 'processing' || o.status === 'pending',
                           'bg-red-500': o.status === 'cancelled',
                           'bg-gray-500': !['completed', 'success', 'processing', 'pending', 'cancelled'].includes(o.status),
                        }"
                      ></span>
                      {{ o.status || 'unknown' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>

    <div>
      <Modal
        :open="openCreate"
        title="Add New Product"
        @close="openCreate = false"
        :key="openCreate ? 'create-open' : 'create-closed'"
      >
        <ProductForm
          :model-value="{
            category_id: '',
            product_name: '',
            price: '',
            stock: '',
            description: '',
            image: null,
          }"
          :categories="categories"
          @submit="createProduct"
          :key="openCreate ? 'create-form-open' : 'create-form-closed'"
        />
      </Modal>

      <Modal
        :open="openEdit"
        title="Edit Product"
        @close="openEdit = false"
        :key="editing?.product_id || 'edit-modal'"
      >
        <ProductForm
          :model-value="editing || {}"
          :categories="categories"
          @submit="updateProduct"
          :key="editing?.product_id || 'edit'"
        />
      </Modal>
    </div>

    <Footer />
  </div>
</template>
