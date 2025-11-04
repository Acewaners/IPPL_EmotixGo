<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] }, // ✅ terima daftar kategori
})
const emit = defineEmits(['submit'])

// state lokal
const local = reactive({
  category_id: '',
  product_name: '',
  price: '',
  stock: '',
  description: '',
  image: null,
})

// sync dari parent tiap kali modelValue berubah
watch(() => props.modelValue, (v) => {
  local.category_id = v?.category_id ?? ''
  local.product_name = v?.product_name ?? ''
  local.price = v?.price ?? ''
  local.stock = v?.stock ?? ''
  local.description = v?.description ?? ''
  local.image = null
}, { immediate: true })

const onFile = (e) => { local.image = e.target.files?.[0] || null }
const submit = () => emit('submit', { ...local })
</script>

<template>
  <div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="text-sm">Kategori</label>
        <select v-model.number="local.category_id" class="w-full border rounded px-3 py-2" required>
          <option disabled value="">Pilih kategori</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div>
        <label class="text-sm">Nama Produk</label>
        <input v-model="local.product_name" class="w-full border rounded px-3 py-2" required />
      </div>
      <div>
        <label class="text-sm">Harga (Rp)</label>
        <input v-model.number="local.price" type="number" min="0" class="w-full border rounded px-3 py-2" required />
      </div>
      <div>
        <label class="text-sm">Stok</label>
        <input v-model.number="local.stock" type="number" min="0" class="w-full border rounded px-3 py-2" required />
      </div>
      <div>
        <label class="text-sm">Gambar</label>
        <input type="file" accept="image/*" @change="onFile" class="w-full border rounded px-3 py-1.5" />
      </div>
    </div>

    <div>
      <label class="text-sm">Deskripsi</label>
      <textarea v-model="local.description" rows="3" class="w-full border rounded px-3 py-2"></textarea>
    </div>

    <div class="flex justify-end gap-2">
      <button @click="submit" class="px-4 py-2 rounded bg-gray-900 text-white">Simpan</button>
    </div>
  </div>
</template>
