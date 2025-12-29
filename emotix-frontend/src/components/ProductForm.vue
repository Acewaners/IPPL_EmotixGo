<script setup>
import { reactive, watch } from 'vue'
import { 
  CloudArrowUpIcon, 
  CheckIcon, 
  TagIcon, 
  DocumentTextIcon, 
  CurrencyDollarIcon, 
  CircleStackIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  // ✅ 1. Tambahkan prop isLoading
  isLoading: { type: Boolean, default: false } 
})

const emit = defineEmits(['submit'])

const local = reactive({
  category_id: '',
  product_name: '',
  price: '',
  stock: '',
  description: '',
  image: null,
})

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
  <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm space-y-8 overflow-hidden">
    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
            <TagIcon class="w-3.5 h-3.5" /> Kategori <span class="text-red-500">*</span>
          </label>
          <div class="relative group">
            <select
              v-model.number="local.category_id"
              class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm appearance-none focus:outline-none focus:ring-2 focus:ring-black focus:bg-white transition-all pr-10 truncate"
              required
            >
              <option disabled value="">Pilih kategori</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 group-focus-within:text-black">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
            <DocumentTextIcon class="w-3.5 h-3.5" /> Nama Produk <span class="text-red-500">*</span>
          </label>
          <input
            v-model="local.product_name"
            type="text"
            class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:ring-2 focus:ring-black focus:bg-white transition-all placeholder-gray-400"
            placeholder="Masukkan nama produk"
            required
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-2">
          <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
            <CurrencyDollarIcon class="w-3.5 h-3.5" /> Harga (Rp) <span class="text-red-500">*</span>
          </label>
          <div class="relative group">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-black font-bold text-xs">Rp</span>
            <input
              v-model.number="local.price"
              type="number"
              min="0"
              class="w-full bg-gray-50/50 border border-gray-200 rounded-xl pl-10 pr-4 py-3.5 text-sm outline-none focus:ring-2 focus:ring-black focus:bg-white transition-all placeholder-gray-400"
              placeholder="0"
              required
            />
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
            <CircleStackIcon class="w-3.5 h-3.5" /> Stok <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="local.stock"
            type="number"
            min="0"
            class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:ring-2 focus:ring-black focus:bg-white transition-all placeholder-gray-400"
            placeholder="0"
            required
          />
        </div>

        <div class="space-y-2">
          <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Gambar Produk</label>
          <label class="flex flex-col items-center justify-center w-full h-[50px] bg-gray-50/50 border border-gray-200 border-dashed rounded-xl cursor-pointer hover:bg-gray-100 hover:border-black transition-all group overflow-hidden">
            <div class="flex items-center gap-2 text-xs text-gray-500 px-3 w-full justify-center">
              <CloudArrowUpIcon class="w-4 h-4 flex-shrink-0 group-hover:text-black transition-colors" />
              <span v-if="!local.image" class="truncate">Pilih gambar</span>
              <span v-else class="truncate font-bold text-black uppercase tracking-tight">{{ local.image.name }}</span>
            </div>
            <input type="file" accept="image/*" @change="onFile" class="hidden" />
          </label>
        </div>
      </div>

      <div class="space-y-2">
        <label class="text-[11px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
          <DocumentTextIcon class="w-3.5 h-3.5" /> Deskripsi
        </label>
        <textarea
          v-model="local.description"
          rows="4"
          class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:ring-2 focus:ring-black focus:bg-white transition-all placeholder-gray-400 resize-none break-words"
          placeholder="Tuliskan deskripsi lengkap produk..."
        ></textarea>
      </div>
    </div>

    <div class="flex justify-end pt-4 border-t border-gray-50">
      <button
        @click="submit"
        :disabled="isLoading"
        class="px-8 py-3.5 bg-black text-white font-black text-xs uppercase tracking-widest rounded-full shadow-lg hover:bg-gray-800 hover:-translate-y-0.5 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:active:scale-100"
      >
        <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        <CheckIcon v-else class="w-4 h-4" />
        
        <span>{{ isLoading ? 'Menyimpan...' : 'Simpan Produk' }}</span>
      </button>
    </div>
  </div>
</template>