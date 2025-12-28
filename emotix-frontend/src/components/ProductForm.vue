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
  <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm space-y-8">
    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="text-sm font-semibold text-gray-700">Kategori <span class="text-red-500">*</span></label>
          <div class="relative">
            <select
              v-model.number="local.category_id"
              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm appearance-none focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-all pr-10 truncate"
              required
            >
              <option disabled value="" class="text-gray-400">Pilih kategori</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-gray-700">Nama Produk <span class="text-red-500">*</span></label>
          <input
            v-model="local.product_name"
            type="text"
            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
            placeholder="Masukkan nama produk"
            required
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-2">
          <label class="text-sm font-semibold text-gray-700">Harga (Rp) <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 text-sm">Rp</span>
            <input
              v-model.number="local.price"
              type="number"
              min="0"
              class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-10 pr-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
              placeholder="0"
              required
            />
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-gray-700">Stok <span class="text-red-500">*</span></label>
          <input
            v-model.number="local.stock"
            type="number"
            min="0"
            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
            placeholder="0"
            required
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-gray-700">Gambar Produk</label>
          <label class="flex flex-col items-center justify-center w-full h-[54px] bg-gray-50 border border-gray-200 border-dashed rounded-xl cursor-pointer hover:bg-gray-100 transition-all">
            <div class="flex items-center gap-2 text-sm text-gray-500">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
              </svg>
              <span v-if="!local.image" class="truncate">Upload File</span>
              <span v-else class="truncate font-medium text-black">{{ local.image.name }}</span>
            </div>
            <input type="file" accept="image/*" @change="onFile" class="hidden" />
          </label>
        </div>
      </div>

      <div class="space-y-2">
        <label class="text-sm font-semibold text-gray-700">Deskripsi</label>
        <textarea
          v-model="local.description"
          rows="4"
          class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400 resize-none"
          placeholder="Tuliskan deskripsi lengkap produk..."
        ></textarea>
      </div>
    </div>

    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button
        @click="submit"
        class="px-8 py-3.5 bg-black text-white font-bold text-sm rounded-full shadow-lg hover:bg-gray-800 hover:shadow-xl hover:-translate-y-0.5 active:scale-95 transition-all flex items-center gap-2"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
          <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
        </svg>
        Simpan Produk
      </button>
    </div>
  </div>
</template>
