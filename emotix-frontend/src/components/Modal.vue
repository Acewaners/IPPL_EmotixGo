<script setup>
defineProps({ open: Boolean, title: String })
defineEmits(['close'])
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>
    <div class="relative bg-white w-full max-w-lg rounded-xl shadow-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-black">✕</button>
      </div>
      <div><slot /></div>
    </div>
  </div>
</template>
