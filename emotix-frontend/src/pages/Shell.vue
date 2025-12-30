<script setup>
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'
const auth = useAuth()
const r = useRouter()
const logout = async () => { await auth.logout(); r.push('/login') }
</script>

<template>
  <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b">
    <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
      <div class="font-semibold">Emotix Dashboard</div>
      <nav class="flex items-center gap-4 text-sm">
        <RouterLink v-if="auth.user?.role==='seller'" to="/seller/products" class="hover:underline">Product</RouterLink>
        <RouterLink v-if="auth.user?.role==='buyer'" to="/buyer/orders" class="hover:underline">Orders</RouterLink>
        <span class="text-gray-500">Hi, {{ auth.user?.name }}</span>
        <button @click="logout" class="px-3 py-1.5 rounded bg-gray-900 text-white text-xs">Logout</button>
      </nav>
    </div>
  </header>
  <main class="max-w-6xl mx-auto px-4 py-6">
    <slot />
  </main>
</template>
