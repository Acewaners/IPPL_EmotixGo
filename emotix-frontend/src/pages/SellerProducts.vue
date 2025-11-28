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

const STORAGE_BASE =
  import.meta.env?.VITE_STORAGE_BASE ?? 'http://localhost:8000/storage'
const imgSrc = (path) => (path ? `${STORAGE_BASE}/${path}` : '')

onMounted(async () => {
  if (!auth.token) return r.push('/login')
  if (auth.user?.role !== 'seller') return r.push('/buyer/orders')
  await Promise.all([loadCategories(), loadProducts(), loadSellerOrders()])
})

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
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 bg-gray-50">
      <section class="max-w-6xl mx-auto px-4 lg:px-0 py-8 space-y-6">
        <!-- Header -->
        <div>
          <h1 class="text-xl font-semibold text-gray-900">
            Seller Dashboard
          </h1>
          <p class="text-sm text-gray-500">
            Welcome back! Here’s your store overview.
          </p>
        </div>

        <!-- Top stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Total Revenue -->
          <div
            class="bg-white rounded-lg shadow-sm border p-4 flex flex-col gap-2"
          >
            <p class="text-xs text-gray-500">Total Revenue</p>
            <p class="text-lg font-semibold">
              Rp {{ totalRevenue.toLocaleString('id-ID') }}
            </p>
          </div>

          <!-- Total Orders -->
          <div
            class="bg-white rounded-lg shadow-sm border p-4 flex flex-col gap-2"
          >
            <p class="text-xs text-gray-500">Total Orders</p>
            <p class="text-lg font-semibold">
              {{ totalOrders }}
            </p>
          </div>

          <!-- Products Sold -->
          <div
            class="bg-white rounded-lg shadow-sm border p-4 flex flex-col gap-2"
          >
            <p class="text-xs text-gray-500">Products Sold</p>
            <p class="text-lg font-semibold">
              {{ totalProductsSold }}
            </p>
          </div>

          <!-- Active Products -->
          <div
            class="bg-white rounded-lg shadow-sm border p-4 flex flex-col gap-2"
          >
            <p class="text-xs text-gray-500">Active Products</p>
            <p class="text-lg font-semibold">
              {{ activeProducts }}
            </p>
          </div>
        </div>

        <!-- Your Products -->
        <div class="bg-white rounded-lg shadow-sm border p-4 md:p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900">
              Your Products
            </h2>
            <button
              @click="openCreate = true"
              class="px-4 py-2 rounded-md bg-red-500 text-white text-sm hover:bg-red-600"
            >
              Add New Product
            </button>
          </div>

          <div v-if="loading" class="text-gray-500 text-sm">Loading...</div>
          <div v-if="error" class="text-red-600 text-sm mb-2">
            {{ error }}
          </div>

          <div v-if="!loading && products.length" class="overflow-x-auto">
            <table class="min-w-full text-xs md:text-sm">
              <thead class="bg-gray-50 text-left">
                <tr>
                  <th class="p-3">Product</th>
                  <th class="p-3">Price</th>
                  <th class="p-3">Stock</th>
                  <th class="p-3">Sold</th>
                  <th class="p-3">Rating</th>
                  <th class="p-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="p in products"
                  :key="p.product_id"
                  class="border-t hover:bg-gray-50"
                >
                  <!-- Product (image + name) -->
                  <td class="p-3">
                    <div class="flex items-center gap-3">
                      <img
                        v-if="p.image"
                        :src="imgSrc(p.image)"
                        class="w-10 h-10 rounded object-cover"
                      />
                      <div>
                        <p class="font-medium text-gray-900">
                          {{ p.product_name }}
                        </p>
                        <p class="text-[11px] text-gray-500">
                          {{ p.category?.name || p.category_name || '–' }}
                        </p>
                      </div>
                    </div>
                  </td>

                  <!-- Price -->
                  <td class="p-3">
                    Rp {{ Number(p.price || 0).toLocaleString('id-ID') }}
                  </td>

                  <!-- Stock -->
                  <td class="p-3">
                    <span
                      class="inline-flex items-center px-2 py-1 rounded-full text-[11px]"
                      :class="{
                        'bg-red-50 text-red-700':
                          Number(p.stock ?? 0) <= 10,
                        'bg-yellow-50 text-yellow-700':
                          Number(p.stock ?? 0) > 10 &&
                          Number(p.stock ?? 0) <= 50,
                        'bg-green-50 text-green-700':
                          Number(p.stock ?? 0) > 50,
                      }"
                    >
                      {{ p.stock ?? 0 }} units
                    </span>
                  </td>

                  <!-- Sold (belum ada data, default 0) -->
                  <td class="p-3">
                    {{ p.sold ?? 0 }}
                  </td>

                  <!-- Rating (default 0) -->
                  <td class="p-3">
  <span>★ {{ p.rating ?? 0 }}</span>
  <span class="text-[11px] text-gray-400" v-if="p.rating_count">
    ({{ p.rating_count }})
  </span>
                  </td>

                  <!-- Actions -->
                  <td class="p-3 space-x-2">
                    <button
                      @click="startEdit(p)"
                      class="px-3 py-1.5 rounded bg-gray-900 text-white text-xs"
                    >
                      Edit
                    </button>
                    <button
                      @click="removeProduct(p)"
                      class="px-3 py-1.5 rounded bg-gray-200 text-xs"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-else-if="!loading" class="text-gray-500 text-sm">
            Belum ada produk.
          </p>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow-sm border p-4 md:p-5">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">
            Recent Orders
          </h2>

          <div v-if="!sellerOrders.length" class="text-gray-500 text-sm">
            Belum ada pesanan.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-xs md:text-sm">
              <thead class="bg-gray-50 text-left">
                <tr>
                  <th class="p-3">Order ID</th>
                  <th class="p-3">Customer</th>
                  <th class="p-3">Product</th>
                  <th class="p-3">Amount</th>
                  <th class="p-3">Date</th>
                  <th class="p-3">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="o in sellerOrders"
                  :key="o.transaction_id"
                  class="border-t hover:bg-gray-50"
                >
                  <td class="p-3">#ORD-{{ o.transaction_id }}</td>
                  <td class="p-3">{{ o.buyer?.name || o.customer_name || '—' }}</td>
                  <td class="p-3">
                    <!-- tampilkan produk pertama -->
                    <span>
                      {{
                        o.details?.[0]?.product?.product_name ||
                        o.details?.[0]?.product_name ||
                        '—'
                      }}
                    </span>
                  </td>
                  <td class="p-3">
                    Rp {{ Number(o.total_price || 0).toLocaleString('id-ID') }}
                  </td>
                  <td class="p-3">
                    {{ o.transaction_date || o.created_at }}
                  </td>
                  <td class="p-3">
                    <span
                      class="inline-flex items-center px-2 py-1 rounded-full text-[11px]"
                      :class="{
                        'bg-green-50 text-green-700 border border-green-200':
                          o.status === 'completed' ||
                          o.status === 'success',
                        'bg-yellow-50 text-yellow-700 border border-yellow-200':
                          o.status === 'processing' ||
                          o.status === 'pending',
                        'bg-red-50 text-red-700 border border-red-200':
                          o.status === 'cancelled',
                        'bg-gray-100 text-gray-700 border border-gray-200':
                          !['completed', 'success', 'processing', 'pending', 'cancelled'].includes(
                            o.status,
                          ),
                      }"
                    >
                      {{ o.status || 'unknown' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>

    <!-- modals -->
    <Modal
      :open="openCreate"
      title="Produk Baru"
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
      title="Edit Produk"
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

    <Footer />
  </div>
</template>
