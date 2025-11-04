<script setup>
defineProps({ title: String, subtitle: String })
</script>

<template>
  <main class="flex-1">
    <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-10 items-center px-4 py-10">
      <div><img src="/auth.jpg" class="w-full rounded-lg shadow-sm object-cover" alt="hero" /></div>
      <div>
        <h2 class="text-3xl font-semibold mb-2">{{ title }}</h2>
        <p v-if="subtitle" class="text-gray-500 mb-8">{{ subtitle }}</p>
        <slot />
      </div>
    </div>
  </main>
</template>
