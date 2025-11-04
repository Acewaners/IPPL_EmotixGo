<script setup>
import { ref, onMounted } from 'vue'
import Shell from './Shell.vue'
import Modal from '../components/Modal.vue'
import ProductForm from '../components/ProductForm.vue'
import { api } from '../lib/api'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuth()
const r = useRouter()
const items = ref([])
const loading = ref(true)
const error = ref('')
const openCreate = ref(false)
const openEdit = ref(false)
const editing = ref(null)

onMounted(async () => {
  if (!auth.token) return r.push('/login')
  if (auth.user?.role !== 'seller') return r.push('/buyer/orders')
  await load()
})

async function load() {
  loading.value = true; error.value=''
  try {
    const { data } = await api.get('/products')
    items.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
  } catch (e) {
    error.value = e?.response?.data?.message || 'Gagal memuat produk'
  } finally {
    loading.value = false
  }
}

async function createProduct(payload) {
  try {
    const form = new FormData()
    form.append('product_name', payload.product_name ?? '')
    form.append('price', payload.price ?? 0)
    form.append('stock', payload.stock ?? 0)
    form.append('description', payload.description ?? '')
    if (payload.image) form.append('image', payload.image)

    await api.post('/products', form, { headers: { 'Content-Type': 'multipart/form-data' } })
    openCreate.value = false
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Gagal membuat produk')
  }
}

function startEdit(row) {
  editing.value = {
    product_id: row.product_id,
    product_name: row.product_name,
    price: row.price,
    stock: row.stock,
    description: row.description ?? '',
    image: null, // supaya input file kosong
  }
  openEdit.value = true
}


async function updateProduct(payload) {
  try {
    const form = new FormData()
    form.append('product_name', payload.product_name ?? '')
    form.append('price', payload.price ?? 0)
    form.append('stock', payload.stock ?? 0)
    form.append('description', payload.description ?? '')
    if (payload.image) form.append('image', payload.image)

    // Pilih salah satu:
    // A) RESTfull murni (server support PUT multipart)
    // await api.put(`/products/${editing.value.product_id}`, form, {
    //   headers: { 'Content-Type': 'multipart/form-data' }
    // })

    // B) Kompatibel luas (method override)
    form.append('_method', 'PUT')
    await api.post(`/products/${editing.value.product_id}`, form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    openEdit.value = false
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Gagal update produk')
  }
}


async function removeProduct(row) {
  if (!confirm(`Hapus produk "${row.product_name}"?`)) return
  try {
    await api.delete(`/products/${row.product_id}`)
    await load()
  } catch (e) {
    alert(e?.response?.data?.message || 'Gagal hapus produk')
  }
}
</script>

<template>
  <Shell>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-xl font-semibold">Produk Saya</h1>
        <p class="text-sm text-gray-500">Kelola katalog toko kamu.</p>
      </div>
      <button @click="openCreate=true" class="px-4 py-2 rounded bg-red-500 text-white">Tambah Produk</button>
    </div>

    <div v-if="loading" class="text-gray-500">Loading...</div>
    <div v-if="error" class="text-red-600">{{ error }}</div>

    <div v-if="!loading && items.length" class="overflow-x-auto border rounded">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left">
          <tr>
            <th class="p-3">ID</th>
            <th class="p-3">Nama</th>
            <th class="p-3">Harga</th>
            <th class="p-3">Stok</th>
            <th class="p-3">Gambar</th>
            <th class="p-3">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in items" :key="p.product_id" class="border-t">
            <td class="p-3">{{ p.product_id }}</td>
            <td class="p-3">{{ p.product_name }}</td>
            <td class="p-3">Rp {{ Number(p.price||0).toLocaleString('id-ID') }}</td>
            <td class="p-3">{{ p.stock }}</td>
            <td class="p-3">
              <img v-if="p.image" :src="`http://localhost:8000/storage/${p.image}`" class="w-12 h-12 object-cover rounded" />
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="p-3 space-x-2">
              <button @click="startEdit(p)" class="px-3 py-1.5 rounded bg-gray-900 text-white">Edit</button>
              <button @click="removeProduct(p)" class="px-3 py-1.5 rounded bg-gray-200">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else-if="!loading" class="text-gray-500">Belum ada produk.</p>

    <!-- Modal: Produk Baru -->
    <Modal
    :open="openCreate"
    title="Produk Baru"
    @close="openCreate=false"
    :key="openCreate ? 'create-open' : 'create-closed'"
    >
    <ProductForm
        :model-value="{ product_name:'', price:'', stock:'', description:'', image:null }"
        @submit="createProduct"
        :key="openCreate ? 'create-form-open' : 'create-form-closed'"
    />
    </Modal>


    <Modal
    :open="openEdit"
    title="Edit Produk"
    @close="openEdit=false"
    :key="editing?.product_id || 'edit-modal'"
    >
    <ProductForm
        :model-value="editing || {}"
        @submit="updateProduct"
        :key="editing?.product_id || 'edit'"
    />
    </Modal>

  </Shell>
</template>
